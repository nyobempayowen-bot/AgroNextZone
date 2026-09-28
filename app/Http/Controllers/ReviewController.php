<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Publication et gestion des avis produits.
 *
 * Sécurité (tout est recalculé côté serveur, MySQL = source de vérité) :
 * - `store` : réservé aux clients, produit réellement acheté, pas
 *   d'auto-évaluation, pas de doublon, note 1..5 (ReviewService + StoreReviewRequest).
 * - `update` / `destroy` : lAvis doit appartenir à l'utilisateur connecté,
 *   contrôlé par ReviewPolicy (403 sinon). Un avis ne peut être modifié ou
 *   supprimé que par son auteur.
 */
class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews)
    {
    }

    /**
     * Publier un avis sur un produit.
     *
     * L'URL contient un slug : le produit est résolu explicitement plutôt que
     * par model binding (qui chercherait un id nommé « slug »).
     */
    public function store(StoreReviewRequest $request, string $slug): RedirectResponse|JsonResponse
    {
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $user = Auth::user();
        $validated = $request->validated();

        $review = $this->reviews->create($user, $product, [
            'rating' => $validated['note'],
            'title' => $validated['titre'],
            'comment' => $validated['commentaire'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Votre avis a été publié.',
                'review' => [
                    'id' => $review->id,
                    'rating' => (int) $review->rating,
                    'title' => $review->title,
                    'comment' => $review->comment,
                ],
            ], 201);
        }

        return redirect()
            ->route('produit', ['slug' => $product->slug])
            ->with('success', 'Merci ! Votre avis a été publié.');
    }

    /**
     * Modifier son propre avis.
     * La note et le titre restent dans les memes bornes que la publication.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        // 403 si l'avis appartient a quelqu'un d'autre (ReviewPolicy).
        \Illuminate\Support\Facades\Gate::authorize('update', $review);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['required', 'string', 'max:150'],
            'comment' => ['required', 'string', 'max:1000'],
        ], [
            'rating.required' => 'Choisissez une note entre 1 et 5 étoiles.',
            'rating.min' => 'La note doit être comprise entre 1 et 5.',
            'rating.max' => 'La note doit être comprise entre 1 et 5.',
            'title.required' => 'Donnez un titre à votre avis.',
            'comment.required' => 'Votre commentaire ne peut pas être vide.',
        ]);

        $review->update([
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Votre avis a été mis à jour.');
    }

    /** Supprimer son propre avis. */
    public function destroy(Review $review): RedirectResponse
    {
        // 403 si l'avis appartient a quelqu'un d'autre (ReviewPolicy).
        \Illuminate\Support\Facades\Gate::authorize('delete', $review);

        $review->delete();

        return back()->with('success', 'Votre avis a été supprimé.');
    }
}
