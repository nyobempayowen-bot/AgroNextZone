<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Recherche catalogue temps réel dans MySQL pour l'assistant.
 *
 * RÈGLE FONDAMENTALE : AgroNextZone = source de vérité COMMERCIALE.
 * Ce service est le SEUL endroit où disponibilité, stock, prix, producteur
 * et localisation sont lus. L'IA (OpenRouter) sert uniquement à comprendre
 * la demande et à formuler — elle ne décide JAMAIS de ce qui est en vente.
 *
 * Ce service est volontairement INDÉPENDANT du fournisseur IA : si OpenRouter
 * tombe, la recherche catalogue reste disponible et exacte. Aucune donnée n'est
 * codée en dur et aucun cache n'est utilisé : chaque appel lit MySQL à l'instant
 * de la question, donc un prix ou un stock modifié est immédiatement reflété.
 */
class CatalogSearchService
{
    /**
     * Réduit une question à ses mots significatifs (mots vides du français
     * et de l'anglais écartés).
     *
     * @return Collection<int, string>
     */
    public function extractTerms(string $message): Collection
    {
        $text = $this->normalize($message);

        $stopWords = [
            'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'on',
            'un', 'une', 'des', 'le', 'la', 'les', 'du', 'de', 'd', 'au', 'aux',
            'ce', 'cet', 'cette', 'ces', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes',
            'son', 'sa', 'ses', 'notre', 'votre', 'leurs', 'est', 'sont', 'ai',
            'as', 'avons', 'avez', 'peux', 'peut', 'pour', 'pouvais', 'vouloir',
            'souhaite', 'souhaites', 'cherche', 'chercher', 'trouve', 'trouver',
            'faire', 'preparer', 'besoin', 'quoi', 'quel', 'quelle', 'quels',
            'quelles', 'ou', 'chez', 'vers', 'a', 'avec', 'sans', 'et',
            'the', 'of', 'have', 'has', 'do', 'does', 'can', 'i', 'want', 'need',
            'find', 'buy', 'purchase', 'get', 'make', 'cook', 'dish', 'is',
            'are', 'am', 'me', 'my', 'some', 'any', 'there', 'vend', 'vendez',
        ];

        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($words)
            ->filter(fn ($w) => mb_strlen($w) >= 3)
            ->reject(fn ($w) => in_array($w, $stopWords, true))
            ->unique()
            ->values();
    }

    /**
     * Dictionnaire des synonymes et appellations locales.
     *
     * Chaque variante est un synonyme réel du même produit alimentaire : on
     * n'élargit pas la recherche au-delà du plausible.
     *
     * @return array<string, array<int, string>>
     */
    private function synonymTable(): array
    {
        return [
            // Feuille amère / Ndolé
            'ndole' => ['feuilles de ndole', 'feuilles amere', 'bitterleaf', 'bitter leaf', 'feuillesameres'],
            'feuilles de ndole' => ['ndole', 'feuilles amere', 'bitterleaf', 'bitter leaf'],
            'feuilles amere' => ['ndole', 'feuilles de ndole', 'bitterleaf', 'bitter leaf'],
            'bitterleaf' => ['ndole', 'feuilles de ndole', 'feuilles amere'],
            'bitter leaf' => ['ndole', 'feuilles de ndole', 'feuilles amere'],
            'amer' => ['feuilles amere', 'ndole', 'bitterleaf'],
            'amere' => ['feuilles amere', 'ndole', 'bitterleaf'],

            // Racines et féculents
            'manioc' => ['cassava', 'yuca', 'mandioca'],
            'cassava' => ['manioc', 'yuca', 'mandioca'],
            'patate douce' => ['sweet potato'],
            'igname' => ['yam'],
            'igname de guine' => ['taro'],

            // Fruits
            'plantain' => ['banane plantain', 'matoke'],
            'banane' => ['plantain', 'matoke'],
            'mangue' => ['mango'],
            'ananas' => ['pineapple'],
            'avocat' => ['avocado'],
            'papaye' => ['papaya'],

            // Légumes
            'tomate' => ['tomates', 'pomodoro'],
            'tomates' => ['tomate'],
            'oignon' => ['onion', 'cebolla'],
            'piment' => ['mangal', 'hot pepper'],
            'ail' => ['garlic'],
            'poivron' => ['bell pepper'],
            'carotte' => ['carrot'],
            'concombre' => ['cucumber'],
            'gombo' => ['okra'],

            // Protéines
            'poisson' => ['fish'],
            'poulet' => ['chicken', 'volaille'],
            'viande' => ['meat', 'boeuf'],
            'boeuf' => ['viande', 'beef'],
            'crevette' => ['shrimp'],
            'arachide' => ['cacahuete', 'peanut'],
            'oeuf' => ['egg'],

            // Condiments
            'huile' => ['huile de palme', 'palm oil'],
            'huile de palme' => ['palm oil'],
            'sel' => ['salt'],

            // Céréales
            'riz' => ['rice'],
            'mais' => ['corn'],
        ];
    }

    /**
     * Variantes lexicales directes d'un terme.
     *
     * @return array<int, string>
     */
    public function synonymsFor(string $term): array
    {
        $table = $this->synonymTable();
        $t = mb_strtolower($this->normalize($term));

        // Le dictionnaire est sans accents : on tente les deux écritures.
        $direct = $table[$t] ?? $table[$this->unaccent($t)] ?? [];

        return array_values(array_unique(array_filter(
            $direct,
            fn ($s) => mb_strtolower($s) !== $t
        )));
    }

    /**
     * Variantes d'un terme, y compris quand le terme n'est qu'un MOT d'une
     * expression du dictionnaire.
     *
     * Indispensable pour « feuilles amères » : ce n'est pas une clé, mais elle
     * CONTIENT « feuilles amere », donc elle doit rattacher le groupe Ndolé
     * (ndole, bitterleaf…).
     *
     * @return array<int, string>
     */
    private function expandSynonyms(string $term): array
    {
        $t = $this->unaccent($term);
        $found = $this->synonymsFor($term);

        foreach ($this->synonymTable() as $key => $variants) {
            $k = $this->unaccent((string) $key);

            if ($k === '' || $k === $t) {
                continue;
            }

            if (str_contains($k, $t) || str_contains($t, $k)) {
                $found[] = (string) $key;
                foreach ((array) $variants as $v) {
                    $found[] = (string) $v;
                }
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Toutes les expressions à chercher dans le catalogue pour une question :
     * termes de la demande, variantes lexicales et combinaisons de deux mots.
     *
     * @return array<int, string>
     */
    public function searchTermsFor(string $message): array
    {
        $terms = $this->extractTerms($message);
        $needles = [];

        foreach ($terms as $term) {
            $needles[] = $term;
            foreach ($this->expandSynonyms($term) as $syn) {
                $needles[] = mb_strtolower($syn);
            }
        }

        $list = $terms->all();
        for ($i = 0; $i < count($list); $i++) {
            for ($j = $i + 1; $j < count($list); $j++) {
                $needles[] = $list[$i].' '.$list[$j];
            }
        }

        return array_values(array_unique(array_filter(
            $needles,
            fn ($n) => mb_strlen(trim($n)) >= 3
        )));
    }

    /**
     * Recherche PRODUITS RÉELS dans MySQL, à l'instant de la question.
     *
     * Ne retourne que des lignes `products` existantes : rien n'est inventé.
     *
     * @param  array<int,string>  $needles
     * @return Collection<int, array>
     */
    public function searchProducts(array $needles, ?string $place = null, int $limit = 12): Collection
    {
        if ($needles === []) {
            return collect();
        }

        // MySQL ne replie pas les accents : « ndole » ne trouve pas « Ndolé ».
        // On préfiltre donc sur des formes SANS ACCENT, et le score final est
        // recalculé en PHP sur les mêmes chaînes normalisées.
        $sqlNeedles = array_values(array_unique(array_map(
            fn ($n) => $this->unaccent((string) $n),
            $needles
        )));

        $tokens = [];
        foreach ($sqlNeedles as $needle) {
            foreach (preg_split('/[^\p{L}\p{N}]+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
                if (mb_strlen($word) >= 4) {
                    $tokens[] = $word;
                }
            }
        }
        $tokens = array_values(array_unique($tokens));

        if ($tokens === []) {
            $tokens = $sqlNeedles;
        }

        $rows = Product::query()
            ->with(['producer', 'category', 'location'])
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $like = '%'.$this->escapeLike($token).'%';

                    $q->orWhereRaw('LOWER(products.name) LIKE ?', [$like])
                      ->orWhereRaw('LOWER(COALESCE(products.description, \'\')) LIKE ?', [$like])
                      ->orWhereHas('category', fn ($cq) => $cq->whereRaw('LOWER(categories.name) LIKE ?', [$like]));
                }
            })
            ->when($place !== null && $place !== '', function ($q) use ($place) {
                $placeKey = $this->unaccent((string) $place);
                $like = '%'.$this->escapeLike($placeKey).'%';
                $q->whereHas('location', fn ($lq) => $lq
                    ->whereRaw('LOWER(COALESCE(locations.city, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(locations.region, \'\')) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(locations.locality, \'\')) LIKE ?', [$like]));
            })
            ->limit(max($limit * 3, 30))
            ->get();

        return $rows
            ->map(fn (Product $product) => $this->describeProduct($product, $needles))
            ->filter()
            ->sortByDesc(fn (array $p) => [$p['relevance'], $p['score']])
            ->take($limit)
            ->values();
    }

    /**
     * Décrit un produit avec ses données RÉELLES et calcule un score.
     *
     * @return array<string,mixed>|null null si la correspondance est trop faible.
     */
    public function describeProduct(Product $product, array $needles): ?array
    {
        $name = $this->unaccent((string) $product->name);
        $description = $this->unaccent((string) $product->description);
        $category = $this->unaccent((string) ($product->category?->name ?? ''));

        $score = 0;
        foreach ($needles as $needle) {
            $needle = $this->unaccent(trim((string) $needle));

            if ($needle === '') {
                continue;
            }

            // Un nom qui commence par le terme bat une mention en description.
            if ($name === $needle) {
                $score += 100;
            } elseif (str_starts_with($name, $needle)) {
                $score += 60;
            } elseif (str_contains($name, $needle)) {
                $score += 40;
            }

            if ($category !== '' && str_contains($category, $needle)) {
                $score += 15;
            }

            if ($description !== '' && str_contains($description, $needle)) {
                $score += 5;
            }

            // Expression en plusieurs mots : si TOUS ses mots figurent dans
            // le nom du produit, c'est une correspondance forte même si
            // l'ordre diffère (« feuilles amere » vs « Feuilles de Ndolé »
            // n'est pas dans le nom, mais « plantain vert » vs « Plantain
            // vert bio » l'est). On creditе par mot present dans le nom.
            $words = preg_split('/[^\p{L}\p{N}]+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (count($words) >= 2) {
                $found = 0;
                foreach ($words as $w) {
                    if (mb_strlen($w) >= 4 && str_contains($name, $w)) {
                        $found++;
                    }
                }

                if ($found === count($words)) {
                    $score += 45;
                } elseif ($found > 0) {
                    $score += 10;
                }
            }
        }

        // Seuil de pertinence : on ne propose PAS un produit qui « ressemble
        // vaguement » au terme demandé.
        if ($score < 40) {
            return null;
        }

        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'price' => (float) $product->price,
            'unit' => (string) $product->unit,
            'stock' => (int) $product->stock_quantity,
            'minimum_order' => (int) $product->minimum_order,
            'producer' => (string) ($product->producer?->name ?? 'Producteur'),
            'location' => $this->formatLocation($product->location),
            'category' => (string) ($product->category?->name ?? ''),
            'purchasable' => $product->isPurchasable(),
            'disponibilite' => $this->availabilityLabel($product),
            'url' => route('produit', ['slug' => $product->slug]),
            'image' => $product->image_url,
            // Fiabilité du producteur 0-100 (moyenne des avis publiés).
            'score' => round(((float) $product->reviews()->avg('rating') / 5) * 100),
            'relevance' => $score,
        ];
    }

    /** Étiquette de disponibilité honnête, calculée sur les données réelles. */
    private function availabilityLabel(Product $product): string
    {
        if (! $product->is_available) {
            return 'Indisponible';
        }

        if ($product->status !== 'published') {
            return 'Non disponible à la vente';
        }

        $stock = (int) $product->stock_quantity;

        if ($stock <= 0) {
            return 'Rupture de stock';
        }

        if ($stock <= 5) {
            return "Plus que {$stock} unités disponibles";
        }

        return 'Disponible';
    }

    /**
     * Localisation RÉELLE du produit, ou null si la base n'en contient aucune.
     * On n'invente jamais de ville.
     */
    private function formatLocation(?Location $location): ?string
    {
        if (! $location) {
            return null;
        }

        $parts = array_filter([$location->locality, $location->city]);

        if ($parts === []) {
            return $location->region ?: null;
        }

        return implode(', ', $parts);
    }

    /**
     * Localisation demandée par l'utilisateur, si elle existe vraiment dans
     * la table `locations`. Jamais de lieu inventé.
     */
    public function resolvePlace(string $message): ?string
    {
        $needle = $this->unaccent($message);

        $places = Location::query()->pluck('city')
            ->merge(Location::query()->pluck('region'))
            ->merge(Location::query()->pluck('locality'))
            ->filter()
            ->map(fn ($c) => trim((string) $c))
            ->unique();

        // 1) Correspondance sur la valeur entiere (« bafia »).
        foreach ($places as $place) {
            $p = $this->unaccent($place);
            if (mb_strlen($p) >= 4 && str_contains($needle, $p)) {
                return $place;
            }
        }

        // 2) Correspondance sur un MOT de la valeur : la base stocke des
        //    libelles composites comme « Centre (yaounde) », qu'un client ne
        //    saisi jamais tels quels (« je cherche du plantain a Yaounde »).
        $words = preg_split('/[^\p{L}\p{N}]+/u', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 4) {
                continue;
            }

            foreach ($places as $place) {
                foreach (preg_split('/[^\p{L}\p{N}]+/u', $this->unaccent($place), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
                    if (mb_strlen($part) >= 4 && $part === $word) {
                        return $place;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Recherche les produits correspondant à chaque ingrédient d'une recette.
     *
     * Pour chaque ingrédient on dit explicitement s'il existe ou non — sans
     * jamais transformer une absence en disponibilité.
     *
     * @param  array<int,string>  $ingredients
     * @return array<int, array{ingredient:string, products:array, found:bool}>
     */
    public function searchForIngredients(array $ingredients, ?string $place = null): array
    {
        $results = [];

        foreach ($ingredients as $ingredient) {
            $ingredient = trim((string) $ingredient);
            if ($ingredient === '') {
                continue;
            }

            $needles = array_values(array_unique(array_merge(
                [mb_strtolower($ingredient)],
                $this->expandSynonyms($ingredient)
            )));

            $products = $this->searchProducts($needles, $place, limit: 4);

            $results[] = [
                'ingredient' => $ingredient,
                'products' => $products->all(),
                'found' => $products->isNotEmpty(),
            ];
        }

        return $results;
    }

    /** Minuscules, espaces multiples réduits. */
    private function normalize(string $value): string
    {
        return (string) preg_replace('/\s+/u', ' ', mb_strtolower(trim($value)));
    }

    /**
     * Retire les accents (« Ndolé » -> « ndole »).
     *
     * Indispensable : un client tape « ndole » sans accent et doit trouver le
     * produit « Ndolé ». Le matching se fait sur la forme sans accent des
     * deux côtés, la chaîne d'origine restant affichée telle quelle.
     */
    private function unaccent(string $value): string
    {
        static $map = [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y', 'œ' => 'oe', 'æ' => 'ae',
        ];

        return strtr(mb_strtolower(trim($value)), $map);
    }

    /** Échappe les jokers SQL LIKE pour qu'un terme ne casse pas la requête. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}