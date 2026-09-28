<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProducerReview;
use App\Models\User;
use App\Services\ProducerReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Notation d'un producteur par un client après une transaction.
 * Le contrôle d'accès (rôle client + ownership de la commande) est
 * refait ici, en plus des vérifications métier du service.
 */
class ProducerReviewController extends Controller
{
    public function __construct(private ProducerReviewService $reviews)
    {
    }

    public function store(Request $request, Order $order, User $producer): RedirectResponse
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'client') {
            abort(403, 'Seuls les clients peuvent noter un producteur.');
        }

        if ((int) $order->client_id !== (int) $user->id) {
            abort(403, 'Cette commande ne vous appartient pas.');
        }

        if ($producer->role !== 'producer') {
            abort(404, 'Producteur introuvable.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Merci de choisir une note de 1 à 5 étoiles.',
            'rating.min' => 'La note doit être comprise entre 1 et 5.',
            'rating.max' => 'La note doit être comprise entre 1 et 5.',
        ]);

        $this->reviews->create($user, $order, $producer, $validated);

        return back()->with('success', 'Merci ! Votre note pour ' . $producer->name . ' a été publiée.');
    }

    /**
     * Modifier sa propre notation.
     * 403 si la notation appartient a un autre client (ProducerReviewPolicy).
     */
    public function update(Request $request, ProducerReview $review): RedirectResponse
    {
        Gate::authorize('update', $review);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Merci de choisir une note de 1 à 5 étoiles.',
            'rating.min' => 'La note doit être comprise entre 1 et 5.',
            'rating.max' => 'La note doit être comprise entre 1 et 5.',
        ]);

        $review->update([
            'rating' => (int) $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return back()->with('success', 'Votre note a été mise à jour.');
    }

    /**
     * Supprimer sa propre notation.
     * 403 si la notation appartient a un autre client (ProducerReviewPolicy).
     */
    public function destroy(ProducerReview $review): RedirectResponse
    {
        Gate::authorize('delete', $review);

        $review->delete();

        return back()->with('success', 'Votre note a été supprimée.');
    }
}
