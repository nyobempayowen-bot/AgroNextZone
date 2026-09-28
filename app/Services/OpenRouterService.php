<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client IA unique d'AgroNextZone — fournisseur OpenRouter.
 *
 * Remplace l'ancien appel Google Gemini (`generateContent`) par l'API OpenRouter,
 * compatible OpenAI : POST {base_url}/chat/completions.
 *
 * Points de conception importants :
 * - La clé N'EST JAMAIS écrite dans le corps de la requête : elle voyage dans
 *   l'en-tête `Authorization: Bearer ...`, comme l'exige l'API OpenRouter.
 *   (L'ancien code Gemini envoyait `key` dans le corps JSON, ce qui renvoyait
 *   un HTTP 400 « Unknown name "key" » et faisait basculer silencieusement
 *   toutes les recommandations sur le repli local.)
 * - La clé ne quitte JAMAIS le serveur : ce service est appelé uniquement depuis
 *   le backend, aucun appel direct n'est fait depuis le JavaScript des vues.
 * - Toutes les erreurs (clé absente/invalide, HTTP 4xx/5xx, 429 rate-limit,
 *   timeout, DNS, corps vide, JSON invalide, modèle indisponible) sont converties
 *   en exceptions \RuntimeException avec un message français non technique.
 *   L'appelant décide de son repli (repli local SQL pour les recommandations,
 *   message honnête pour l'assistant).
 * - Aucun secret n'est écrit dans les logs : seuls le statut HTTP, le code
 *   d'erreur OpenRouter et un message tronqué sont journalisés.
 */
class OpenRouterService
{
    /** Clé API lue depuis .env uniquement. */
    public function apiKey(): string
    {
        return (string) config('services.openrouter.key');
    }

    /** URL de base normalisée (sans slash final). */
    public function baseUrl(): string
    {
        return rtrim((string) config('services.openrouter.base_url'), '/');
    }

    /** Identifiant du modèle, routeur gratuit par défaut. */
    public function model(): string
    {
        return (string) config('services.openrouter.model');
    }

    /**
     * True uniquement quand une clé est réellement disponible.
     * Permet à l'appelant de court-circuiter proprement sans appel réseau.
     */
    public function isConfigured(): bool
    {
        return trim($this->apiKey()) !== '' && $this->baseUrl() !== '';
    }

    /**
     * Envoie un prompt et retourne le contenu texte de la première réponse.
     *
     * @param  string               $systemInstruction  Rôle système (facultatif).
     * @param  string|array<string> $userPrompt          Message utilisateur (string) ou liste de messages.
     * @param  float                $temperature         Température du modèle.
     * @param  int                  $maxTokens           Tokens max générés.
     *
     * @throws \RuntimeException sur toute défaillance (message français, sans secret).
     */
    public function complete(
        string $systemInstruction,
        string|array $userPrompt,
        float $temperature = 0.4,
        int $maxTokens = 800,
    ): string {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'Service IA non configuré : OPENROUTER_API_KEY absente du fichier .env.'
            );
        }

        // OpenAI-compatible : une liste de messages {role, content}.
        $messages = [];
        if (trim($systemInstruction) !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }
        foreach (is_array($userPrompt) ? $userPrompt : [$userPrompt] as $part) {
            $messages[] = ['role' => 'user', 'content' => (string) $part];
        }

        $payload = [
            'model' => $this->model(),
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        // Le routeur gratuit peut Selectionner un modèle de « raisonnement » qui
        // consomme tout le budget de tokens avant de produire sa reponse finale
        // (content vide, finish_reason=length). On retente alors une fois avec un
        // budget plus large plutot que de laisser l'appelant sur son repli.
        $budget = $maxTokens;
        $lastError = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::withHeaders($this->headers())
                    ->timeout((int) config('services.openrouter.timeout', 20))
                    ->connectTimeout((int) config('services.openrouter.connect_timeout', 10))
                    ->acceptJson()
                    ->post($this->baseUrl().'/chat/completions', $payload + ['max_tokens' => $budget]);
            } catch (\Throwable $e) {
                // Timeout, DNS, TLS, OpenRouter indisponible...
                Log::warning('OpenRouter: appel réseau impossible.', [
                    'model' => $this->model(),
                    'error' => $e->getMessage(),
                ]);

                throw new \RuntimeException('Service IA momentanément injoignable.');
            }

            try {
                return $this->extractText($response);
            } catch (\RuntimeException $e) {
                $lastError = $e;

                if ($attempt === 1 && str_contains($e->getMessage(), 'vide')) {
                    $budget = $maxTokens * 2;
                    Log::info('OpenRouter: réponse vide, nouvelle tentative avec un budget plus large.', [
                        'model' => $this->model(),
                        'max_tokens' => $budget,
                    ]);
                    continue;
                }

                throw $e;
            }
        }

        throw $lastError ?? new \RuntimeException('Réponse IA vide.');
    }

    /**
     * Extrait le texte de la réponse OpenRouter en format OpenAI.
     * Gère le streaming désactivé (une seule choice) et les contenus textuels
     * uniquement — les images renvoyées par certains modèles sont ignorées.
     */
    protected function extractText(Response $response): string
    {
        if (! $response->successful()) {
            throw new \RuntimeException($this->describeHttpError($response));
        }

        $text = $response->json('choices.0.message.content');

        if (! is_string($text) || trim($text) === '') {
            // Certains routeurs renvoient un tableau de fragments (content[]).
            $text = $this->extractFromContentParts($response);
        }

        if (! is_string($text) || trim($text) === '') {
            throw new \RuntimeException('Réponse IA vide.');
        }

        return trim($text);
    }

    /** Repli : réponse structurée en `message.content` tableau de fragments. */
    protected function extractFromContentParts(Response $response): ?string
    {
        $parts = $response->json('choices.0.message.content');

        if (! is_array($parts)) {
            return null;
        }

        $buffer = '';
        foreach ($parts as $part) {
            if (is_string($part)) {
                $buffer .= $part;
            } elseif (is_array($part) && is_string($part['text'] ?? null)) {
                $buffer .= $part['text'];
            }
        }

        return $buffer !== '' ? $buffer : null;
    }

    /**
     * Traduit une erreur HTTP OpenRouter en message français actionnable.
     * Aucun secret n'est recopié dans le message.
     */
    protected function describeHttpError(Response $response): string
    {
        $status = $response->status();

        $detail = (string) ($response->json('error.message') ?? '');
        $code = $response->json('error.code');
        $detail = $detail !== '' ? $detail : (string) $response->body();
        $detail = mb_substr(trim(preg_replace('/\s+/', ' ', $detail) ?? ''), 0, 300);

        $message = match (true) {
            $status === 401 => 'Clé API OpenRouter absente ou invalide (401).',
            $status === 402 => 'Crédits OpenRouter insuffisants (402).',
            $status === 403 => 'Accès refusé par OpenRouter (403).',
            $status === 404 => sprintf('Modèle "%s" indisponible chez OpenRouter (404).', $this->model()),
            $status === 408 => 'Délai dépassé côté OpenRouter (408).',
            $status === 429 => 'Limite de requêtes OpenRouter atteinte (429).',
            $status >= 500 => 'OpenRouter est momentanément indisponible (HTTP '.$status.').',
            default => 'Erreur OpenRouter (HTTP '.$status.').',
        };

        Log::warning('OpenRouter: appel refuse.', [
            'model' => $this->model(),
            'status' => $status,
            'code' => is_scalar($code) ? $code : null,
            'detail' => $detail,
        ]);

        return $message.' '.$detail;
    }

    /**
     * En-têtes de la requête. La clé est lue depuis .env et placée dans
     * l'en-tête Authorization uniquement.
     */
    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // Métadonnées affichées dans le tableau de bord OpenRouter.
            'HTTP-Referer' => (string) config('services.openrouter.app_url'),
            'X-Title' => (string) config('services.openrouter.app_name'),
        ];
    }

    /**
     * Décode une réponse texte en JSON, en tolérant les blocs markdown
     ```json … ``` que certains modèles renvoient malgré une consigne JSON.
     *
     * @throws \RuntimeException si le JSON est illisible.
     */
    public function decodeJson(string $text): array
    {
        $clean = trim($text);
        $clean = trim((string) preg_replace('/^```(?:json)?\s*|\s*```$/mi', '', $clean));

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Le JSON peut etre enchasse dans du texte libre : on extrait le
        // premier objet equilibre {...} en ignorant les accolades en chaine.
        $object = $this->firstBalancedObject($clean);
        if ($object !== null) {
            $decoded = json_decode($object, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Dernier recours : une suite d'objets simples separes par des virgules.
        $objects = [];
        if (preg_match_all('/\{[^{}]*\}/', $clean, $matches)) {
            foreach ($matches[0] as $candidate) {
                $d = json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($d)) {
                    $objects[] = $d;
                }
            }
        }
        if ($objects !== []) {
            return count($objects) === 1 ? $objects[0] : $objects;
        }

        throw new \RuntimeException('Réponse IA au format JSON invalide.');
    }

    /**
     * Retourne le premier objet JSON equilibre {...} d'une chaine de caractere,
     * en gerant les echappements et les accolades presentes dans une chaine.
     */
    protected function firstBalancedObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }
}
