<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocodage des adresses producteurs.
 *
 * Source principale : Geoapify (voir app/Services/GeoapifyService.php).
 * Replis conserves pour la resilience : Google Geocoding puis Nominatim.
 *
 * Failures NEVER block the caller: the profile is saved with null coordinates
 * and stays visible through the classic region/city filter.
 */
class GeocodingService
{
    private const ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const NOMINATIM_ENDPOINT = 'https://nominatim.openstreetmap.org/search';

    public function __construct(private readonly GeoapifyService $geoapify)
    {
    }

    /**
     * Geocode an address; returns ['lat' => float, 'lng' => float] or null on any failure.
     *
     * Priority: Geoapify (rapide, fiable sur le Cameroun, villes ET zones
     * rurales). Replis successifs si l'API est indisponible : Google Geocoding
     * puis Nominatim (OpenStreetMap, gratuit, sans cle) — un producteur doit
     * toujours obtenir des coordonnees.
     */
    public function geocode(string $address): ?array
    {
        if (trim($address) === '') {
            return null;
        }

        return $this->geoapify->geocode($address)
            ?? $this->geocodeGoogle($address)
            ?? $this->geocodeNominatim($address);
    }

    private function geocodeGoogle(string $address): ?array
    {
        $key = (string) config('services.google_maps.key');

        if ($key === '' || trim($address) === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->get(self::ENDPOINT, [
                    'address' => $address,
                    'key' => $key,
                ]);

            $payload = $response->json();

            // API-level status (REQUEST_DENIED, OVER_QUERY_LIMIT, ZERO_RESULTS...).
            if (($payload['status'] ?? '') !== 'OK') {
                Log::warning('Geocoding API returned a non-OK status', [
                    'address' => $address,
                    'status' => $payload['status'] ?? 'missing',
                    'message' => $payload['error_message'] ?? null,
                ]);

                return null;
            }

            $location = $payload['results'][0]['geometry']['location'] ?? null;

            if (! isset($location['lat'], $location['lng'])) {
                return null;
            }

            return ['lat' => (float) $location['lat'], 'lng' => (float) $location['lng']];
        } catch (\Throwable $e) {
            // Timeout, DNS failure, quota, malformed response... non-blocking.
            Log::error('Geocoding request failed', ['address' => $address, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Free fallback geocoder (OpenStreetMap Nominatim). Returns the same
     * ['lat', 'lng'] shape, or null on any failure. Usage policy requires
     * a descriptive User-Agent and a low request rate.
     */
    private function geocodeNominatim(string $address): ?array
    {
        if (trim($address) === '') {
            return null;
        }

        try {
            $response = Http::timeout(10)
                ->retry(2, 500)
                ->withHeaders([
                    'User-Agent' => 'AgroNextZone/1.0 (agricultural marketplace; contact@agronextzone.com)',
                    'Accept' => 'application/json',
                ])
                ->get(self::NOMINATIM_ENDPOINT, [
                    'q' => $address,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);

            $results = $response->json();

            if (! is_array($results) || $results === []) {
                return null;
            }

            $first = $results[0];

            if (! isset($first['lat'], $first['lon'])) {
                return null;
            }

            return ['lat' => (float) $first['lat'], 'lng' => (float) $first['lon']];
        } catch (\Throwable $e) {
            Log::error('Nominatim geocoding request failed', ['address' => $address, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Geocode a producer profile from his stored address fields and persist
     * the coordinates on the profile row (null on failure, never throws).
     */
    public function geocodeProducer(\App\Models\User $producer): void
    {
        $profile = $producer->producerProfile()->first();

        if (! $profile) {
            return;
        }

        $addressParts = array_filter([
            $profile->farm_name,
            $producer->adresse,
            $producer->region,
        ], fn ($part) => ! empty(trim((string) $part)));

        if ($addressParts === []) {
            return;
        }

        $address = implode(', ', $addressParts).', Cameroun';
        $coordinates = $this->geocode($address);

        if ($coordinates === null) {
            return; // Graceful degradation: coordinates stay null.
        }

        $profile->update([
            'latitude' => $coordinates['lat'],
            'longitude' => $coordinates['lng'],
            'geocoded_at' => now(),
        ]);
    }
}
