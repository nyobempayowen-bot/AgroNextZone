<?php

namespace App\Http\Controllers;

use App\Services\GeoapifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Routes de geolocalisation pour le front.
 *
 * Le navigateur ne parle JAMAIS directement a Geoapify : il appelle ces deux
 * routes, et Laravel interroge l'API avec la cle stockee dans .env. La cle
 * n'apparait donc jamais dans le JavaScript, le Blade, le HTML ni le JSON
 * envoye au client.
 *
 * Les deux routes renvoient toujours un JSON structure et comprehensible :
 * en cas d'echec, `ok: false` + un message francais, jamais une erreur 500.
 */
class GeoController extends Controller
{
    public function __construct(private readonly GeoapifyService $geoapify)
    {
    }

    /**
     * Geocodage inverse : latitude + longitude -> ville / region / quartier.
     *
     * POST /geolocation/reverse
     */
    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'latitude.required' => 'Latitude manquante.',
            'latitude.between' => 'Latitude invalide.',
            'longitude.required' => 'Longitude manquante.',
            'longitude.between' => 'Longitude invalide.',
        ]);

        if (! $this->geoapify->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => 'Localisation automatique indisponible. Saisissez votre ville manuellement.',
            ], 503);
        }

        try {
            $place = $this->geoapify->reverse(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
            );
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true, 'place' => $place]);
    }

    /**
     * Recherche d'adresse saisie par l'utilisateur.
     *
     * GET /geolocation/search?query=Yaounde, Cameroun
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:150'],
        ], [
            'query.required' => 'Saisissez une ville ou une adresse.',
            'query.min' => 'Saisissez au moins 3 caractères.',
        ]);

        if (! $this->geoapify->isConfigured()) {
            return response()->json([
                'ok' => false,
                'message' => 'Recherche d\'adresse indisponible. Saisissez votre ville manuellement.',
            ], 503);
        }

        try {
            $places = $this->geoapify->search($validated['query'], 5);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 200);
        }

        return response()->json(['ok' => true, 'places' => $places]);
    }
}
