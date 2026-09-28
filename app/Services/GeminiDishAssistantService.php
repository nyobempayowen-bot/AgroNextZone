<?php

namespace App\Services;

use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        // (a) Recherche locale dans la base de connaissance.
        $matches = $this->findDishes($message);

        // (b)+(c) Prompt + appel IA.
        try {
            $aiResult = $this->callOpenRouter($client, $message, $matches);

            $reply = $aiResult['reply'];
            $ingredients = $aiResult['ingredients'];
            $knownDish = $matches->isNotEmpty();
        } catch (\Throwable $e) {
            Log::warning('DishAssistant: appel OpenRouter échoué.', ['error' => $e->getMessage()]);

            return [
                'reply' => "L'assistant est temporairement indisponible. Veuillez réessayer dans quelques instants.",
                'products' => [],
                'known_dish' => $matches->isNotEmpty(),
                'api_error' => true,
            ];
        }

        // (d) Vrais produits, triés par fiabilité producteur (réutilise le
        //     même score que le service existant) puis prix.
        $products = $this->findRealProducts($ingredients);

        // (f) Historique en session.
        $this->rememberTurn($client, $message, $reply);

        return [
            'reply' => $reply,
            'products' => $products->values()->all(),
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
    protected function callOpenRouter(User $client, string $message, Collection $matches): array
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

        $prompt = <<<PROMPT
Historique récent de la conversation (peut être vide):
{$this->historyJson($history)}

Message actuel du client: "{$message}"

Base de connaissance des plats locaux (SEULE source de vérité autorisée, peut être vide):
{$this->knowledgeJson($knowledge)}
PROMPT;

        $systemInstruction = "Tu es l'assistant repas d'une marketplace agricole camerounaise. "
            ."Règles STRICTES : (1) La composition des plats provient UNIQUEMENT de la base de connaissance fournie ci-dessous. "
            ."Si aucun plat de cette base ne correspond à la demande, dis honnêtement au client que tu ne connais pas encore ce plat "
            ."et propose-lui de préciser lui-même ses ingrédients — n'invente JAMAIS la composition d'un plat. "
            ."(2) Réponds en français, de façon conversationnelle et utile. "
            ."(3) Réponds UNIQUEMENT avec du JSON valide, sans texte autour, au format exact : "
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

        return Product::query()
            ->select([
                'products.id', 'products.name', 'products.slug', 'products.price', 'products.unit',
                'users.name AS producer',
                DB::raw('COALESCE(AVG(reviews.rating), 0) AS rating'),
            ])
            ->join('users', 'users.id', '=', 'products.producer_id')
            ->leftJoin('reviews', 'reviews.product_id', '=', 'products.id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.is_available', true)
            ->where('products.status', 'published')
            ->where('products.stock_quantity', '>', 0)
            ->where(function ($q) use ($ingredients) {
                $q->whereIn('categories.name', $ingredients)
                    ->orWhereIn('products.name', $ingredients)
                    ->orWhere(function ($q2) use ($ingredients) {
                        foreach ($ingredients as $ing) {
                            $q2->orWhere('products.name', 'LIKE', '%'.$ing.'%');
                            $q2->orWhere('categories.name', 'LIKE', '%'.$ing.'%');
                        }
                    });
            })
            ->groupBy('products.id', 'products.name', 'products.slug', 'products.price', 'products.unit', 'users.name')
            ->orderByDesc('rating')
            ->orderBy('products.price')
            ->limit(12)
            ->get()
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => (float) $p->price,
                'unit' => $p->unit,
                'producer' => $p->producer,
                'score' => round(((float) $p->rating / 5) * 100), // 0-100
            ]);
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
