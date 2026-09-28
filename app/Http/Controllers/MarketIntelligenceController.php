<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiRecommendationService;
use App\Services\MarketPriceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Bloc C (2/2) — Marché des prix + Recommandations IA (Gemini).
 */
class MarketIntelligenceController extends Controller
{
    public function __construct(
        protected MarketPriceService $marketPrices,
        protected GeminiRecommendationService $recommendations,
    ) {
    }

    /** Marché des prix — agrégation SQL locale (pas d'appel IA). */
    public function prices()
    {
        return view('market-prix', [
            'stats' => $this->marketPrices->stats(),
        ]);
    }

    /**
     * Recommandations IA (Gemini) — JSON, réservé aux clients connectés.
     * La bulle côté client appelle cet endpoint en asynchrone ; la clé API
     * ne quitte jamais le serveur.
     */
    public function recommendations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $items = $this->recommendations->recommend($user);

        return response()->json([
            'recommendations' => $items->values(),
        ]);
    }
}
