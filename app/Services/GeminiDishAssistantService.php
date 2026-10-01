<?php

namespace App\Services;

use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Bloc D — Assistant repas conversationnel (chat dédié, tour par tour).
 *
 * Principes :
 * - La composition des plats vient UNIQUEMENT de la base `local_dishes`
 *   (renseignée par l'admin). L'IA ne doit jamais inventer : le prompt
 *   système l'exige explicitement, et si aucun plat n'est trouvé en base
 *   on l'indique à l'IA pour qu'elle le dise honnêtement.
 * - Les produits proposés sont de VRAIS produits MySQL, disponibles,
 *   triés par score de fiabilité du Producteur (moyenne des notes),
 *   puis par prix. Aucun produit halluciné.
 * - Historique de conversation conservé en session (léger, borné).
 * - Panne de l'API : message de repli propre, jamais de crash.
 *
 * Fournisseur IA : OpenRouter (migration depuis Google Gemini). L'appel HTTP
 * est centralisé dans OpenRouterService — la clé reste côté serveur, dans
 * l'en-tête Authorization, et n'apparaît jamais dans le corps de la requête.
 */
class GeminiDishAssistantService
{
    public function __construct(
        private readonly OpenRouterService $ai,
        private readonly CatalogSearchService $catalog,
    ) {
    }

    /** Nombre max d'échanges conservés en session. */
    private const HISTORY_TURNS = 8;

    /** Historique conversationnel en session (borné). */
    public function history(User $client): array
    {
        return session("dish_assistant_{$client->id}", []);
    }

    public function resetHistory(User $client): void
    {
        session()->forget("dish_assistant_{$client->id}");
    }

    /**
     * Traite un message du client et retourne la réponse complète.
     *
     * @return array{reply:string, products:array, known_dish:bool, api_error:bool}
     */
    public function handle(User $client, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['reply' => 'Posez-moi votre question : quel plat souhaitez-vous préparer ?', 'products' => [], 'known_dish' => false, 'api_error' => false];
        }

        // (a) Recherche locale dans la base de connaissance des plats.
        $matches = $this->findDishes($message);

        // (b) RECHERCHE CATALOGUE TEMPS RÉEL — AVANT d'appeler l'IA.
        // C'est ce qui permet à l'assistant de répondre « j'ai vérifié »
        // au lieu de « je n'ai pas accès aux stocks ». MySQL est la source
        // de vérité commerciale ; l'IA ne fait que le reformuler.
        $place = $this->catalog->resolvePlace($message);
        $needles = $this->catalog->searchTermsFor($message);
        $catalogue = $this->catalog->searchProducts($needles, $place);

        // (c) Prompt + appel IA, enrichi par le catalogue réel.
        try {
            $aiResult = $this->callOpenRouter($client, $message, $matches, $catalogue);

            $reply = $aiResult['reply'];
            $ingredients = $aiResult['ingredients'];
            $knownDish = $matches->isNotEmpty();
        } catch (\Throwable $e) {
            Log::warning('DishAssistant: appel OpenRouter échoué.', ['error' => $e->getMessage()]);

            return [
                'reply' => "L'assistant est temporairement indisponible. Veuillez réessayer dans quelques instants.",
                'products' => $catalogue->values()->all(),
                'catalogue' => $catalogue->values()->all(),
                'catalogue_checked' => true,
                'known_dish' => $matches->isNotEmpty(),
                'api_error' => true,
            ];
        }

        // (d) Vrais produits : d'abord ceux trouvés par la recherche directe
        //     sur la question, puis ceux correspondant aux ingrédients de la
        //     recette que l'IA a identifiés.
        $products = $this->mergeProducts($catalogue, $this->findRealProducts($ingredients));

        // (f) Historique en session.
        $this->rememberTurn($client, $message, $reply);

        return [
            'reply' => $reply,
            'products' => $products->values()->all(),
            // Le catalogue réellement interrogé, transmis au front pour que
            // l'utilisateur voie la source de ses informations.
            'catalogue' => $catalogue->values()->all(),
            'catalogue_checked' => true,
            'known_dish' => $knownDish,
            'api_error' => false,
        ];
    }

    /* ------------------------------------------------------------------ */

    /**
     * Recherche simple par mots-clés : le message contient-il un nom de
     * plat ou un ingrédient connu ?
     */
    protected function findDishes(string $message): Collection
    {
        $needle = mb_strtolower($message);

        $byName = LocalDish::query()
            ->with('ingredients')
            ->get()
            ->filter(fn (LocalDish $d) => mb_strtolower($d->name) !== '' && mb_stripos($needle, mb_strtolower($d->name)) !== false);

        if ($byName->isNotEmpty()) {
            return $byName;
        }

        // Sinon : plats contenant un ingrédient mentionné.
        $ingredientNames = LocalDishIngredient::query()->pluck('ingredient_name')->unique();

        $mentioned = $ingredientNames->filter(fn ($n) => mb_stripos($needle, mb_strtolower($n)) !== false);

        if ($mentioned->isEmpty()) {
            return collect();
        }

        return LocalDish::query()
            ->with('ingredients')
            ->whereHas('ingredients', fn ($q) => $q->whereIn('ingredient_name', $mentioned->values()))
            ->get();
    }

    /** Appel IA réel via OpenRouter (même fournisseur que le service de recommandation). */
    protected function callOpenRouter(User $client, string $message, Collection $matches, Collection $catalogue): array
    {
        if (! $this->ai->isConfigured()) {
            throw new \RuntimeException('OPENROUTER_API_KEY absente du fichier .env');
        }

        $knowledge = $matches->map(fn (LocalDish $d) => [
            'nom' => $d->name,
            'region' => $d->region,
            'description' => $d->description,
            'ingredients' => $d->ingredients->map(fn ($i) => [
                'nom' => $i->ingredient_name,
                'optionnel' => $i->is_optional,
            ])->all(),
        ])->values()->all();

        $history = collect($this->history($client))
            ->map(fn ($t) => ['role' => $t['role'], 'text' => $t['text']])
            ->take(-self::HISTORY_TURNS)
            ->values()
            ->all();

        $catalogueData = $catalogue->map(fn (array $p): array => [
            'nom' => $p['name'],
            'prix' => $p['price'],
            'unite' => $p['unit'],
            'stock' => $p['stock'],
            'disponibilite' => $p['disponibilite'],
            'producteur' => $p['producer'],
            'localisation' => $p['location'],
            'lien' => $p['url'],
        ])->values()->all();

        $catalogueJson = $catalogueData === []
            ? 'AUCUN PRODUIT TROUVÉ dans le catalogue AgroNextZone pour cette demande.'
            : $this->knowledgeJson($catalogueData);

        $prompt = <<<PROMPT
Historique récent de la conversation (peut être vide):
{$this->historyJson($history)}

Message actuel du client: "{$message}"

Base de connaissance des plats locaux (SEULE source de vérité autorisée pour la composition des plats, peut être vide):
{$this->knowledgeJson($knowledge)}

CATALOGUE AgroNextZone — REQUÊTE MYSQL RÉALISÉE À L'INSTANT (source de vérité COMMERCIALE) :
{$catalogueJson}
PROMPT;

        $systemInstruction = "Tu es l'assistant d'une marketplace agricole camerounaise, AgroNextZone. "
            ."Règles STRICTES : "
            ."(1) Le catalogue ci-dessus est le RÉSULTAT D'UNE RECHERCHE RÉELLE dans la base de données d'AgroNextZone, "
            ."effectuée à l'instant. Tu AS accès aux produits, prix, stocks et producteurs de la plateforme. "
            ."NE DIS JAMAIS « je n'ai pas accès aux stocks » ni « je ne peux pas vérifier la disponibilité » : "
            ."si le catalogue est fourni ci-dessus, tu connais la disponibilité réelle. "
            ."(2) Si le catalogue indique qu'aucun produit n'a été trouvé, dis-le honnêtement et propose des alternatives "
            ."réellement listées — n'invente JAMAIS de produit, de prix, de stock, de producteur ou de localisation. "
            ."(3) Distingue clairement ce qui est une connaissance générale (ex. « le Ndolé se prépare généralement avec… ») "
            ."de ce qui est réellement disponible sur AgroNextZone (ex. « j'ai trouvé 3 produits disponibles »). "
            ."Internet ou tes connaissances peuvent expliquer une recette, mais SEULE la base AgroNextZone "
            ."détermine la disponibilité commerciale. "
            ."(4) La composition des plats provient UNIQUEMENT de la base de connaissance des plats fournie. "
            ."(5) Réponds en français, de façon conversationnelle, courte et utile. "
            ."(6) Réponds UNIQUEMENT avec du JSON valide, sans texte autour, au format exact : "
            ."{\"reply\":\"<ta réponse conversationnelle>\",\"ingredients\":[\"<ingrédient 1>\",\"<ingrédient 2>\",...]} "
            ."où ingredients liste les ingrédients/catégories de produits à rechercher dans le catalogue.";

        // Clé API injectée par OpenRouterService dans l'en-tête Authorization :
        // jamais dans le corps de la requête ni dans l'URL.
        // Marge confortable : le routeur gratuit peut renvoyer un modèle
        // « raisonnement » qui consomme une partie du budget avant de répondre.
        $text = $this->ai->complete($systemInstruction, $prompt, temperature: 0.4, maxTokens: 1500);

        $decoded = $this->ai->decodeJson($text);

        $reply = (string) ($decoded['reply'] ?? '');
        $ingredients = array_values(array_filter(
            array_map(fn ($i) => is_string($i) ? trim($i) : '', (array) ($decoded['ingredients'] ?? [])),
            fn ($i) => $i !== ''
        ));

        if ($reply === '') {
            throw new \RuntimeException('Réponse IA sans texte exploitable');
        }

        return ['reply' => $reply, 'ingredients' => $ingredients];
    }

    private function historyJson(array $history): string
    {
        return json_encode($history, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    private function knowledgeJson(array $knowledge): string
    {
        return json_encode($knowledge, JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /**
     * Vrais produits disponibles, correspondant aux ingrédients/catégories,
     * triés par score de fiabilité producteur (moyenne des notes reçues),
     * puis par prix. Même logique de scoring que le service existant.
     */
    protected function findRealProducts(array $ingredients): Collection
    {
        if ($ingredients === []) {
            return collect();
        }

        $needles = [];
        foreach ($ingredients as $ing) {
            $ing = trim((string) $ing);
            if ($ing === '') {
                continue;
            }
            $needles[] = mb_strtolower($ing);
            foreach ($this->catalog->synonymsFor($ing) as $syn) {
                $needles[] = mb_strtolower($syn);
            }
        }

        // Même source de vérité que la recherche directe : MySQL via le service
        // catalogue. Un produit sans stock ou non publié n'est jamais proposé.
        return $this->catalog->searchProducts(array_values(array_unique($needles)), limit: 8);
    }

    /**
 * Fusionne les produits trouvés par la recherche directe sur la question
 * avec ceux trouvés via les ingrédients de la recette, sans doublon.
 *
 * La recherche directe passe en premier : elle répond à ce que le client a
 * réellement demandé. Aucune fusion ne peut inventer un produit : les deux
 * collections proviennent exclusivement de MySQL.
 */
protected function mergeProducts(Collection $direct, Collection $fromRecipe): Collection
{
    $merged = collect();
    $seen = [];

    foreach ($direct->concat($fromRecipe) as $product) {
        $id = (int) ($product['id'] ?? 0);

        if ($id === 0 || isset($seen[$id])) {
            continue;
        }

        $seen[$id] = true;
        $merged->push($product);
    }

    return $merged->take(12)->values();
}

protected function rememberTurn(User $client, string $message, string $reply): void
    {
        $key = "dish_assistant_{$client->id}";
        $history = session($key, []);
        $history[] = ['role' => 'client', 'text' => $message];
        $history[] = ['role' => 'assistant', 'text' => $reply];
        session([$key => array_slice($history, -self::HISTORY_TURNS * 2)]);
    }
}
