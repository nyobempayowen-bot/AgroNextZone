<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProducerReview;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Notation directe d'un producteur par un client après une transaction.
 *
 * Règles :
 * - seuls les clients authentifiés peuvent noter ;
 * - le client doit avoir passé la commande visée ET cette commande doit
 *   correspondre à une transaction réelle (paiement réussi ou statut
 *   « livré ») — les données de session ne sont jamais considérées fiables ;
 * - le producteur évalué doit être un vrai producteur présent dans la
 *   commande (jamais le client lui-même) ;
 * - un seul avis par commande et par client (index unique MySQL en secours) ;
 * - note strictement comprise entre 1 et 5.
 */
class ProducerReviewService
{
    /** @return array{eligible: bool, reason: string|null} */
    public function canReview(User $client, Order $order, User $producer): array
    {
        if ($client->role !== 'client') {
            return ['eligible' => false, 'reason' => 'Seuls les clients peuvent noter un producteur.'];
        }

        if ((int) $order->client_id !== (int) $client->id) {
            return ['eligible' => false, 'reason' => 'Cette commande ne vous appartient pas.'];
        }

        // Transaction réelle : commande livrée ou paiement réussi.
        $isReal = $order->status === 'delivered'
            || $order->payments()->where('status', 'paid')->exists();

        if (! $isReal) {
            return ['eligible' => false, 'reason' => 'Vous pourrez noter ce producteur une fois la commande payée et livrée.'];
        }

        // Le producteur doit faire partie des vendeurs de cette commande.
        $orderBelongsToProducer = $order->items()
            ->where('producer_id', $producer->id)
            ->exists();

        if (! $orderBelongsToProducer) {
            return ['eligible' => false, 'reason' => 'Ce producteur ne fait pas partie de cette commande.'];
        }

        if ((int) $producer->id === (int) $client->id) {
            return ['eligible' => false, 'reason' => 'Vous ne pouvez pas vous évaluer vous-même.'];
        }

        if ($producer->role !== 'producer') {
            return ['eligible' => false, 'reason' => 'Ce compte n\'est pas un producteur.'];
        }

        if (ProducerReview::query()
            ->where('order_id', $order->id)
            ->where('client_id', $client->id)
            ->where('producer_id', $producer->id)
            ->exists()) {
            return ['eligible' => false, 'reason' => 'Vous avez déjà noté ce producteur pour cette commande.'];
        }

        return ['eligible' => true, 'reason' => null];
    }

    public function create(User $client, Order $order, User $producer, array $data): ProducerReview
    {
        $check = $this->canReview($client, $order, $producer);

        if (! $check['eligible']) {
            throw ValidationException::withMessages(['note' => $check['reason']]);
        }

        $rating = (int) ($data['rating'] ?? 0);

        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['note' => 'La note doit être comprise entre 1 et 5.']);
        }

        return ProducerReview::create([
            'producer_id' => $producer->id,
            'client_id' => $client->id,
            'order_id' => $order->id,
            'rating' => $rating,
            'comment' => $data['comment'] ?? null,
            'status' => 'published',
        ]);
    }

    /**
     * Score public d'un producteur : moyenne et nombre d'avis publiés.
     *
     * @return array{average: float|null, count: int}
     */
    public function producerScore(User $producer): array
    {
        $query = ProducerReview::query()
            ->where('producer_id', $producer->id)
            ->where('status', 'published');

        $count = (clone $query)->count();
        $average = $count === 0 ? null : (float) $query->avg('rating');

        return ['average' => $average !== null ? round($average, 1) : null, 'count' => $count];
    }

    /**
     * Statistiques detailles d'un producteur, calculees depuis MySQL uniquement.
     *
     * AUCUNE valeur par defaut n'est inventee : sans aucun avis publie,
     * `average` vaut null et la repartition est nulle. La vue affiche alors
     * « Pas encore noté » au lieu d'une note inventee.
     *
     * @return array{average: float|null, count: int, distribution: array<int, int>}
     */
    public function producerStats(User $producer): array
    {
        $score = $this->producerScore($producer);

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = ProducerReview::query()
                ->where('producer_id', $producer->id)
                ->where('status', 'published')
                ->where('rating', $star)
                ->count();
        }

        return [
            'average' => $score['average'],
            'count' => $score['count'],
            'distribution' => $distribution,
        ];
    }

    /**
     * La note que CE client a deja donnee a CE producteur, sur la commande passee.
     * Sert a afficher « Vous avez deja note » ou une possibilite de modification.
     */
    public function existingReviewFor(User $client, Order $order, User $producer): ?ProducerReview
    {
        return ProducerReview::query()
            ->where('order_id', $order->id)
            ->where('client_id', $client->id)
            ->where('producer_id', $producer->id)
            ->first();
    }

    /** Derniers avis publiés reçus par un producteur (page profil). */
    public function latestForProducer(User $producer, int $limit = 5): array
    {
        return ProducerReview::query()
            ->where('producer_id', $producer->id)
            ->where('status', 'published')
            ->with('client')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ProducerReview $review): array => [
                'id' => $review->id,
                'auteur' => $this->maskName($review->client?->name),
                'avatar' => $review->client?->avatar_url,
                'note' => (int) $review->rating,
                'date' => $review->created_at->diffForHumans(),
                'date_iso' => $review->created_at->toDateString(),
                'commentaire' => (string) $review->comment,
            ])
            ->all();
    }

    /**
     * Masque l'identite de l'auteur : prenom + initiale du nom.
     * Un avis ne doit pas exposer l'identite complete d'un client.
     */
    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Client Anonyme';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? (string) end($parts) : '';

        return $last !== ''
            ? $first.' '.mb_strtoupper(mb_substr($last, 0, 1)).'.'
            : $first;
    }
}
