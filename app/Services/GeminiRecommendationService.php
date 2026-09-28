<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bloc C (2/2) — Recommandations IA via l'API OpenRouter.
 *
 * Migration Gemini -> OpenRouter : le fournisseur est desormais OpenRouter
 * (API compatible OpenAI, cle envoyee dans l'en-tete Authorization: Bearer).
 * L'API publique de ce service est INCHANGEE : recommend(User) retourne la
 * meme Collection, cacheKey() et forgetFor() sont preserves, donc
 * MarketIntelligenceController, OrderService et la bulle IA n'ont pas besoin
 * d'etre modifies.
 *
 * - Contexte rassemble depuis MySQL (jamais de donnees personnelles :
 *   pas d'email, telephone, adresse exacte ni document CNI).
 * - Requete HTTP via la facade Laravel (Guzzle) vers {base}/chat/completions.
 * - Reponse attendue : JSON strict. Parsing sur, IDs inexistants ignores.
 * - Repli local si l'appel echoue : meilleures offres par score de
 *   fiabilite producteur + prix (calculees en SQL).
 * - Cache par utilisateur (OPENROUTER_CACHE_TTL minutes), invalide a
 *   chaque nouvelle commande (voir forgetFor).
 */
class GeminiRecommendationService
{
    public function __construct(
        private readonly OpenRouterService $ai,
    ) {
    }

    /** Clé de cache par utilisateur. */
    public static function cacheKey(int $userId): string
    {
        return "ai_reco_user_{$userId}";
    }

    /** Invalide le cache de recommandation d'un client (nouvelle commande). */
    public static function forgetFor(int $userId): void
    {
        Cache::forget(self::cacheKey($userId));
    }

    /**
     * Point d'entrée principal : recommandations pour un client.
     * Ne lève jamais d'exception vers l'appelant (page jamais cassée).
     *
     * @return Collection<int, array{id:int,name:string,slug:string,price:float,unit:string,region:?string,producer:string,reason:string,source:string}>
     */
    public function recommend(User $client): Collection
    {
        if ($client->role !== 'client') {
            return collect();
        }

        return Cache::remember(
            self::cacheKey($client->id),
            now()->addMinutes((int) config('services.openrouter.cache_ttl', 45)),
            function () use ($client) {
                return $this->resolve($client);
            }
        );
    }

    /** Résout les recommandations (OpenRouter, sinon repli local). */
    protected function resolve(User $client): Collection
    {
        $offers = $this->candidateOffers($client);
        if ($offers->isEmpty()) {
            return collect();
        }

        if (! $this->ai->isConfigured()) {
            Log::warning('AI Recommendation: OPENROUTER_API_KEY absente, repli local utilisé.');

            return $this->localFallback($offers, 'local');
        }

        try {
            $recommended = $this->callOpenRouter($client, $offers);

            return $this->mapToProducts($recommended, $offers, 'openrouter');
        } catch (\Throwable $e) {
            Log::warning('AI Recommendation: appel OpenRouter échoué, repli local utilisé.', [
                'error' => $e->getMessage(),
            ]);

            return $this->localFallback($offers, 'local');
        }
    }

    /* ---------------------------------------------------------------------
     | Contexte MySQL
     | --------------------------------------------------------------------- */

    /**
     * Offres candidates (bornées, ex. 40 max) avec score de fiabilité producteur.
     * Score fiabilité = moyenne des notes reçues par le producteur (reviews),
     * normalisée sur 100 ; défaut 50 si aucune review.
     */
    protected function candidateOffers(User $client): Collection
    {
        $max = (int) config('services.openrouter.max_offers', 40);

        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.slug',
                'products.price',
                'products.unit',
                'users.region',
                DB::raw("COALESCE(users.name, '') AS producer"),
                DB::raw('COALESCE(categories.name, "") AS category'),
                DB::raw('COALESCE(AVG(reviews.rating), 0) AS rating'),
            ])
            ->join('users', 'users.id', '=', 'products.producer_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('reviews', 'reviews.product_id', '=', 'products.id')
            ->where('products.is_available', true)
            ->where('products.status', 'published')
            ->where('products.stock_quantity', '>', 0)
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.price', 'products.unit', 'users.region', 'users.name', 'categories.name')
            ->orderByDesc('rating')
            ->orderBy('products.price')
            ->limit($max)
            ->get();
    }

    /** Historique d'achat du client (noms produits + catégories), borné. */
    protected function purchaseHistory(User $client): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.client_id', $client->id)
            ->groupBy('order_items.product_name', 'categories.name')
            ->orderByDesc(DB::raw('SUM(order_items.quantity)'))
            ->limit(15)
            ->get([
                DB::raw('order_items.product_name AS product'),
                DB::raw('MAX(categories.name) AS category'),
                DB::raw('SUM(order_items.quantity) AS total_qty'),
            ])
            ->map(fn ($r) => ['product' => $r->product, 'category' => $r->category, 'qty' => (int) $r->total_qty])
            ->all();
    }

    /* ---------------------------------------------------------------------
     | Appel OpenRouter (fournisseur IA)
     | --------------------------------------------------------------------- */

    /**
     * Appel HTTP réel à l'API OpenRouter (chat/completions, format OpenAI).
     * Retourne la liste d'IDs produits recommandés.
     *
     * @param  Collection  $offers
     * @return array<int, array{id:int, reason:string}>
     */
    protected function callOpenRouter(User $client, Collection $offers): array
    {
        $offerLines = $offers->map(fn ($o) => [
            'id' => (int) $o->id,
            'name' => $o->name,
            'category' => $o->category ?: null,
            'price' => (float) $o->price,
            'unit' => $o->unit,
            'region' => $o->region,
            'producer_reliability' => round(((float) $o->rating / 5) * 100), // 0-100
        ])->values()->all();

        $history = $this->purchaseHistory($client);

        $prompt = $this->buildPrompt($client, $offerLines, $history);

        $systemInstruction = "Tu es un moteur de recommandation pour une marketplace agricole. "
            ."Tu reponds UNIQUEMENT avec du JSON valide, sans texte autour, sans balises markdown. "
            ."Format attendu exact : {\"recommendations\":[{\"id\":<int produit existant>,\"reason\":\"<justification courte en francais>\"}]}. "
            ."Utilise uniquement les IDs presents dans la liste fournie (3 a 5 recommandations maximum).";

        // La cle API est injectee par OpenRouterService dans l'en-tete
        // Authorization: Bearer : elle n'apparait jamais dans le corps ni l'URL.
        // Marge confortable : le routeur gratuit peut renvoyer un modèle
        // « raisonnement » qui consomme une partie du budget avant de répondre.
        $text = $this->ai->complete($systemInstruction, $prompt, temperature: 0.4, maxTokens: 2000);

        return $this->parseAiResponse($text);
    }

    /** Construit le prompt (données NON personnelles uniquement). */
    protected function buildPrompt(User $client, array $offerLines, array $history): string
    {
        $jsonOffers = json_encode($offerLines, JSON_UNESCAPED_UNICODE);
        $jsonHistory = json_encode($history, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Contexte client (données agrégées, non personnelles) :
- Région: {$client->region}
- Historique d'achat: {$jsonHistory}

Offres disponibles (id, nom, catégorie, prix, unité, région, fiabilité producteur 0-100):
{$jsonOffers}

Tâche : recommande 3 à 5 offres pertinentes pour ce client. Réponds UNIQUEMENT avec le JSON valide au format demandé.
PROMPT;
    }

    /** Parsing sécurisé de la réponse IA (OpenRouter). */
    protected function parseAiResponse(string $text): array
    {
        try {
            $decoded = $this->ai->decodeJson($text);

            $list = $decoded['recommendations'] ?? $decoded['recommendation'] ?? null;
            if (! is_array($list)) {
                throw new \RuntimeException('Structure JSON IA inattendue');
            }

            $result = [];
            foreach ($list as $item) {
                // Tolere `id` et `product_id` pour ne dependre d'un seul champ.
                $id = $item['id'] ?? $item['product_id'] ?? null;
                if ($id !== null && is_numeric($id)) {
                    $result[] = ['id' => (int) $id, 'reason' => (string) ($item['reason'] ?? 'Recommandé pour vous.')];
                }
            }

            return $result;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Erreur parsing IA : '.$e->getMessage(), 0, $e);
        }
    }

    /* ---------------------------------------------------------------------
     | Repli local + mapping
     | --------------------------------------------------------------------- */

    /**
     * Correspond les IDs renvoyés par l'IA aux vrais produits.
     * Tout ID inexistant / non actif est ignoré silencieusement.
     *
     * @param  array  $recommended
     * @param  Collection  $offers
     * @param  string  $source
     * @return Collection
     */
    protected function mapToProducts(array $recommended, Collection $offers, string $source): Collection
    {
        $byId = $offers->keyBy(fn ($o) => (int) $o->id);

        return collect($recommended)
            ->map(fn ($r) => isset($byId[(int) $r['id']]) ? ['offer' => $byId[(int) $r['id']], 'reason' => $r['reason']] : null)
            ->filter()
            ->take(5)
            ->map(fn ($r) => $this->formatOffer($r['offer'], $r['reason'], $source))
            ->values();
    }

    /**
     * Repli local : meilleures offres par fiabilité producteur puis prix.
     */
    protected function localFallback(Collection $offers, string $source): Collection
    {
        return $offers
            ->sortByDesc(fn ($o) => ((float) $o->rating / 5) * 100)
            ->sortBy(fn ($o) => (float) $o->price)
            ->take(5)
            ->map(fn ($o) => $this->formatOffer($o, 'Sélection basée sur les meilleures offres disponibles.', $source))
            ->values();
    }

    /** Formate une offre pour la vue / API. */
    protected function formatOffer($offer, string $reason, string $source): array
    {
        return [
            'id' => (int) $offer->id,
            'name' => (string) $offer->name,
            'slug' => (string) $offer->slug,
            'price' => (float) $offer->price,
            'unit' => (string) $offer->unit,
            'region' => $offer->region ?: null,
            'producer' => (string) $offer->producer,
            'reason' => $reason,
            'source' => $source,
        ];
    }
}
