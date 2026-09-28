<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Mission 13 — persistent MySQL reviews.
 *
 * Rules enforced here:
 * - only authenticated clients can review (a producer can never review);
 * - the client must have REALLY purchased the product (a MySQL order of
 *   his own containing it, with a successful payment or a delivered
 *   status — session data is never trusted);
 * - a producer can never review his own product (no self-review);
 * - one review per client per product (MySQL unique index as backstop);
 * - rating strictly between 1 and 5.
 *
 * MySQL is the single source of truth: every rule below is decided from
 * database rows, never from the session.
 */
class ReviewService
{
    public function assertPurchased(User $client, Product $product): Order
    {
        $order = Order::query()
            ->where('client_id', $client->id)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->where(function ($q) {
                // Real purchase = paid payment OR an order actually delivered.
                $q->where('status', 'delivered')
                  ->orWhereHas('payments', fn ($p) => $p->where('status', 'paid'));
            })
            ->orderByDesc('id')
            ->first();

        if (! $order) {
            throw ValidationException::withMessages([
                'avis' => 'Seul un client ayant réellement acheté ce produit peut l\'évaluer.',
            ]);
        }

        return $order;
    }

    public function create(User $user, Product $product, array $data): Review
    {
        if ($user->role !== 'client') {
            abort(403, 'Seuls les clients peuvent publier une évaluation.');
        }

        // No self-review: the producer of the product can never rate it.
        if ((int) $product->producer_id === (int) $user->id) {
            abort(403, 'Vous ne pouvez pas évaluer votre propre produit.');
        }

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['note' => 'La note doit être comprise entre 1 et 5.']);
        }

        $existing = Review::query()->where('product_id', $product->id)->where('client_id', $user->id)->first();
        if ($existing) {
            throw ValidationException::withMessages(['avis' => 'Vous avez déjà évalué ce produit.']);
        }

        $order = $this->assertPurchased($user, $product);

        return Review::create([
            'product_id' => $product->id,
            'client_id' => $user->id,
            'order_id' => $order->id,
            'rating' => $rating,
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_verified_purchase' => true,
            'status' => 'published',
        ]);
    }

    /**
     * Producer score: average rating and review count over ALL reviews
     * attached to the products he owns (computed from MySQL rows only).
     *
     * @return array{average: float|null, count: int}
     */
    public function producerScore(User $producer): array
    {
        $query = Review::query()
            ->where('status', 'published')
            ->whereHas('product', fn ($q) => $q->where('producer_id', $producer->id));

        $count = (clone $query)->count();
        $average = $count === 0 ? null : (float) $query->avg('rating');

        return ['average' => $average !== null ? round($average, 1) : null, 'count' => $count];
    }

    /**
     * Rating statistics of a product, computed from MySQL rows only.
     * No invented default: a product without published review has
     * `average === null` and `count === 0`.
     *
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    public function productStats(Product $product): array
    {
        $query = Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'published');

        $count = (clone $query)->count();
        $average = $count === 0 ? null : (float) $query->avg('rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (clone $query)->where('rating', $star)->count();
        }

        return [
            'average' => $average !== null ? round($average, 1) : null,
            'count' => $count,
            'distribution' => $distribution,
        ];
    }

    /**
     * Latest published reviews of a product, ready for Blade.
     * The author is exposed as first name + initial only, so a review does
     * not hand out a client's full identity.
     */
    public function forProduct(Product $product, int $limit = 20): array
    {
        return Review::query()
            ->where('product_id', $product->id)
            ->where('status', 'published')
            ->with('client')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (Review $review): array {
                $name = trim((string) ($review->client?->name ?? ''));
                $parts = preg_split('/\s+/', $name) ?: [];
                $first = $parts[0] ?? '';
                $last = count($parts) > 1 ? (string) end($parts) : '';

                return [
                    'id' => $review->id,
                    'auteur' => $first !== ''
                        ? $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.'
                        : 'Client Anonyme',
                    'avatar' => $review->client?->avatar_url,
                    'note' => (int) $review->rating,
                    'date' => $review->created_at->diffForHumans(),
                    'date_iso' => $review->created_at->toDateString(),
                    'titre' => (string) $review->title,
                    'commentaire' => (string) $review->comment,
                    'achat_verifie' => (bool) $review->is_verified_purchase,
                ];
            })
            ->all();
    }
}
