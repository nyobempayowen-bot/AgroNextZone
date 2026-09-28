<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client Geoapify — geocodage et geocodage inverse.
 *
 * Remplace la source principale de geolocalisation, jusqu'ici Google Geocoding
 * (qui renvoyait REQUEST_DENIED car la facturation GCP n'etait pas activee)
 * avec un repli Nominatim. Geoapify est plus fiable sur le Cameroun et
 * couvre aussi bien les zones rurales (fermes, villages) que les villes.
 *
 * Securite :
 * - la cle vit UNIQUEMENT dans .env (config('services.geoapify.key')) ;
 * - elle est envoyee en query param a Geoapify, jamais au navigateur ;
 * - aucun secret n'apparait dans les logs (statut + code d'erreur seulement).
 *
 * Robustesse :
 * - chaque appel est mis en cache (30 jours par defaut) : un geocodage est
 *   stable dans le temps, inutile de le re-demander a chaque visite ;
 * - toutes les erreurs deviennent des exceptions \RuntimeException au
 *   message francais et non technique ; l'appelant decide de son repli
 *   (GeocodingService bascule alors sur Nominatim).
 */
class GeoapifyService
{
    /** Code ISO du Cameroun renvoye par Geoapify. */
    private const COUNTRY_CODE = 'CM';

    /** Cle de cache lue depuis .env (jamais codee en dur). */
    public function apiKey(): string
    {
        return trim((string) config('services.geoapify.key'));
    }

    public function baseUrl(): string
    {
        return rtrim((string) config('services.geoapify.base_url'), '/');
    }

    /** True uniquement quand une cle est reellement configuree. */
    public function isConfigured(): bool
    {
        return $this->apiKey() !== '' && $this->baseUrl() !== '';
    }

    /**
     * Geocodage inverse : coordonnees GPS -> informations de localisation.
     *
     * @return array{
     *     latitude: float, longitude: float,
     *     country: ?string, region: ?string, city: ?string,
     *     locality: ?string, address: ?string, formatted: ?string
     * }
     *
     * @throws \RuntimeException si aucun resultat exploitable.
     */
    public function reverse(float $latitude, float $longitude): array
    {
        $this->assertValidCoordinates($latitude, $longitude);

        $key = $this->cacheKey('reverse', round($latitude, 5).','.round($longitude, 5));
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request('/geocode/reverse', [
            'lat' => $latitude,
            'lon' => $longitude,
        ]);

        $result = $response->json('results.0');
        if (! is_array($result)) {
            throw new \RuntimeException('Position non reconnue. Essayez de saisir votre ville manuellement.');
        }

        $place = $this->format($result);
        Cache::put($key, $place, (int) config('services.geoapify.cache_ttl', 2592000));

        return $place;
    }

    /**
     * Recherche d'adresse saisie par l'utilisateur.
     *
     * @return array<int, array<string, mixed>> liste de suggestions.
     *
     * @throws \RuntimeException si l'API ne repond pas ou ne trouve rien.
     */
    public function search(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            throw new \RuntimeException('Saisissez au moins 3 caracteres.');
        }

        $limit = max(1, min($limit, 10));
        $key = $this->cacheKey('search', mb_strtolower($query).'|'.$limit);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->request('/geocode/search', [
            'text' => $query,
            'limit' => $limit,
        ]);

        $results = $response->json('results');
        if (! is_array($results) || $results === []) {
            throw new \RuntimeException('Aucune adresse trouvee. Verifiez l\'orthographe ou essayez une autre ville.');
        }

        $places = array_values(array_filter(array_map(
            fn ($item) => is_array($item) ? $this->format($item) : null,
            $results
        )));

        if ($places === []) {
            throw new \RuntimeException('Aucune adresse trouvee pour cette recherche.');
        }

        Cache::put($key, $places, (int) config('services.geoapify.cache_ttl', 2592000));

        return $places;
    }

    /**
     * Geocodage direct d'une adresse en texte libre -> coordonnees.
     * Raccourci utilise par GeocodingService pour les producteurs.
     *
     * @return array{lat: float, lng: float}|null null si introuvable.
     */
    public function geocode(string $address): ?array
    {
        try {
            $places = $this->search($address, 1);
        } catch (\RuntimeException) {
            return null;
        }

        $place = $places[0] ?? null;

        return ($place && isset($place['latitude'], $place['longitude']))
            ? ['lat' => (float) $place['latitude'], 'lng' => (float) $place['longitude']]
            : null;
    }

    /* ------------------------------------------------------------------ */

    /**
     * Appel HTTP commun : ajoute la cle, le format et la langue.
     *
     * @throws \RuntimeException sur toute erreur (message francais).
     */
    protected function request(string $path, array $params): Response
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Service de localisation non configure.');
        }

        try {
            $response = Http::timeout((int) config('services.geoapify.timeout', 10))
                ->connectTimeout((int) config('services.geoapify.connect_timeout', 5))
                ->acceptJson()
                ->get($this->baseUrl().$path, $params + [
                    'format' => 'json',
                    // Nom de lieu et pays en francais : indispensable pour
                    // afficher « Cameroun » et non « Cameroon ».
                    'lang' => 'fr',
                    'apiKey' => $this->apiKey(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Geoapify: appel réseau impossible.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Service de localisation momentanément injoignable.');
        }

        return $this->assertSuccess($response);
    }

    /**
     * Verifie la reponse HTTP. Les codes 4xx/5xx de Geoapify sont traduits
     * en messages comprensibles, jamais en erreur technique brute.
     */
    protected function assertSuccess(Response $response): Response
    {
        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();

        $message = match (true) {
            $status === 401, $status === 403 => 'Service de localisation non configure ou cle invalide.',
            $status === 404 => 'Position ou adresse introuvable.',
            $status === 429 => 'Trop de recherches de localisation. Reessayez dans un instant.',
            $status === 408 => 'Le service de localisation ne repond pas. Reessayez.',
            $status >= 500 => 'Service de localisation indisponible. Vous pouvez saisir votre ville manuellement.',
            default => 'Recherche de localisation impossible pour le moment.',
        };

        // Aucun secret recopie : seulement le statut et le code Geoapify.
        Log::warning('Geoapify: appel refusé.', [
            'status' => $status,
            'code' => $response->json('error.code'),
            'message' => is_string($response->json('error.message'))
                ? mb_substr($response->json('error.message'), 0, 200)
                : null,
        ]);

        throw new \RuntimeException($message);
    }

    /**
     * Normalise un resultat Geoapify vers la forme attendue par la table
     * `locations` (country / region / city / locality / address).
     */
    protected function format(array $item): array
    {
        $country = $this->clean($item['country'] ?? null) ?? $this->countryFromCode($item['country_code'] ?? null);

        return [
            'latitude' => (float) ($item['lat'] ?? 0),
            'longitude' => (float) ($item['lon'] ?? 0),
            'country' => $country,
            // Geoapify renvoie « Région du Centre » : on retire le préfixe
            // « Région » pour que la valeur corresponde exactement aux
            // <option> du formulaire d'inscription (« Centre », « Ouest »…).
            'region' => $this->cleanRegion($this->firstOf([
                $item['state'] ?? null,   // region la plus fiable chez Geoapify
                $item['county'] ?? null,
            ])),
            'city' => $this->firstOf([
                $item['city'] ?? null,
                $item['town'] ?? null,
                $item['village'] ?? null,   // zones rurales camerounaises
                $item['state'] ?? null,
            ]),
            'locality' => $this->firstOf([
                $item['suburb'] ?? null,
                $item['neighbourhood'] ?? null,
                $item['quarter'] ?? null,
                $item['district'] ?? null,
            ]),
            'address' => $this->clean($item['formatted'] ?? null),
            'formatted' => $this->clean($item['formatted'] ?? null),
        ];
    }

    /** Trim + nettoyage des valeurs vides de Geoapify. */
    private function clean($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '' || $value === 'null' || $value === 'undefined') {
            return null;
        }

        return mb_substr($value, 0, 255);
    }

    /** Premiere valeur non vide d'une liste. */
    private function firstOf(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $clean = $this->clean($candidate);
            if ($clean !== null) {
                return $clean;
            }
        }

        return null;
    }

    /**
     * Normalise une region pour qu'elle corresponde exactement aux <option>
     * du formulaire d'inscription (« Centre », « Ouest », « Littoral »…) :
     *   « Région du Centre » -> « Centre »
     *   « l'Ouest »          -> « Ouest »
     *   « Littoral Region »  -> « Littoral »
     */
    private function cleanRegion(?string $region): ?string
    {
        if ($region === null) {
            return null;
        }

        $region = trim($region);

        // Préfixe geoapify : « Région du Centre », « Région de l'Adamaoua ».
        $region = preg_replace('/^r[eé]gion\s+(du\s+|de\s+|de\s+l\x27|de\s+la\s+|d\x27)?/iu', '', $region);
        // Article en tete : « l'Ouest », « l'Ouest », « le Sud ».
        $region = preg_replace('/^(l\x27|le|la|les)\s*/iu', '', (string) $region);
        // Suffixe parasite.
        $region = preg_replace('/\s+(region|r[eé]gion)$/iu', '', (string) $region);
        $region = trim((string) $region);

        if ($region === '') {
            return null;
        }

        // Premiere lettre en majuscule : « ouest » -> « Ouest ».
        return mb_convert_case(mb_substr($region, 0, 1), MB_CASE_TITLE) . mb_substr($region, 1);
    }

    /** « CM » -> « Cameroun » : la table locations stocke un libelle lisible. */
    private function countryFromCode($code): ?string
    {
        return strtoupper((string) $code) === self::COUNTRY_CODE ? 'Cameroun' : null;
    }

    /** Garde-fou : refuse les coordonnees impossibles avant d'appeler l'API. */
    private function assertValidCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \RuntimeException('Coordonnees invalides.');
        }

        // 0,0 est le « point null » du Golfe de Guinée : une position GPS
        // plausible au Cameroun n'est jamais exactement 0,0.
        if ($latitude === 0.0 && $longitude === 0.0) {
            throw new \RuntimeException('Position GPS non exploitable.');
        }
    }

    /** Cle de cache, prefixee par le fournisseur pour rester lisible. */
    private function cacheKey(string $kind, string $value): string
    {
        return "geoapify:{$kind}:" . md5($value);
    }
}
