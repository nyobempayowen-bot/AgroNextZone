<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GeminiDishAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bloc D — Assistant repas conversationnel (client uniquement).
 */
class DishAssistantController extends Controller
{
    public function __construct(protected GeminiDishAssistantService $assistant)
    {
    }

    public function index(Request $request): View
    {
        /** @var User $client */
        $client = $request->user();

        return view('assistant-repas', [
            'history' => $this->assistant->history($client),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        /** @var User $client */
        $client = $request->user();

        $validated = $request->validate(['message' => 'required|string|max:1000']);

        $result = $this->assistant->handle($client, $validated['message']);

        return response()->json($result);
    }

    public function reset(Request $request): JsonResponse
    {
        /** @var User $client */
        $client = $request->user();

        $this->assistant->resetHistory($client);

        return response()->json(['ok' => true]);
    }

    /** Historique de conversation en JSON (utilisé par la bulle AgroBot). */
    public function history(Request $request): JsonResponse
    {
        /** @var User $client */
        $client = $request->user();

        return response()->json(['history' => $this->assistant->history($client)]);
    }
}
