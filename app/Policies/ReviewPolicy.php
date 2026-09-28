<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

/**
 * Bloc B — Review authorization.
 *
 * A Client may only edit/hide HIS OWN evaluations. Publishing stays in
 * ReviewService (verified purchase, no self-review, no duplicate).
 */
class ReviewPolicy
{
    public function update(User $user, Review $review): bool
    {
        return (int) $review->client_id === (int) $user->id;
    }

    public function delete(User $user, Review $review): bool
    {
        return $this->update($user, $review);
    }
}
