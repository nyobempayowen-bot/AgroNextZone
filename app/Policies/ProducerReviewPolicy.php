<?php

namespace App\Policies;

use App\Models\ProducerReview;
use App\Models\User;

/**
 * Autorisations sur les notations de producteur.
 *
 * Un avis de notation appartient a son auteur : seul lui peut le modifier ou
 * le supprimer. La publication, elle, reste reglee par ProducerReviewService
 * (achat réel, pas d'auto-notation, pas de doublon pour une meme commande).
 */
class ProducerReviewPolicy
{
    /** Seul l'auteur peut modifier sa notation. */
    public function update(User $user, ProducerReview $review): bool
    {
        return (int) $review->client_id === (int) $user->id;
    }

    /** Seul l'auteur peut supprimer sa notation. */
    public function delete(User $user, ProducerReview $review): bool
    {
        return $this->update($user, $review);
    }
}
