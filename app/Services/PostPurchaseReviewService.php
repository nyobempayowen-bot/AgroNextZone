<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProducerReview;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Invitation a la notation apres un achat reellement confirme.
 *
 * Principe : la notation est TOUJOURS facultative. Ce service ne fait
 * qu'INFORMER le client de ce qu'il a le droit de noter. Il ne bloque
 * jamais un paiement, une commande ou une navigation.
 *
 * Source de verite : MySQL exclusivement (orders, order_items, payments,
 * reviews, producer_reviews). Le panier, la session et la requete HTTP
 * ne jouent aucun role dans l'eligibilite.
 *
 * Regle metier : un achat = un statut d'ordre « confirme metier » (livree)
 * OU un paiement « paid ». Une commande annulee ou merely « initiatee »
 * n'ouvre aucun droit.
 */
class PostPurchaseReviewService
{
    public function __construct(
        private readonly ReviewService $reviews,
        private readonly ProducerReviewService $producerReviews,
    ) {
    }

    /**
     * Statuts d'ordre considered comme « achat reellement effectue ».
     *
     * On reutilise les statuts EXISTANTS de Order::STATUSES : aucun statut
     * n'est invente. `delivered` est la preuve materielle ; un paiement
     * `paid` est la preuve financiere (traitee plus bas).
     */
    public const PURCHASED_STATUSES = ['delivered'];

    /**
     * L'ordre donne est-il un achat effectif pour le client fourni ?
     */
    public function isConfirmedPurchase(Order $order, User $client): bool
    {
        if ((int) $order->client_id !== (int) $client->id) {
            return false;
        }

        // Une commande annulee n'est jamais un achat, meme si un paiement
        // « paid » traine encore en base.
        if ($order->status === 'cancelled') {
            return false;
        }

        if (in_array($order->status, self::PURCHASED_STATUSES, true)) {
            return true;
        }

        return $order->payments()->where('status', 'paid')->exists();
    }

    /**
     * Produits de CETTE commande que ce client peut encore noter (produit).
     *
     * Un produit deja note est exclu de la liste ; c'est le seul moyen
     * d'eviter d'invitater a une double notation.
     *
     * @return Collection<int, array>
     */
    public function reviewableProductsFor(Order $order, User $client): Collection
    {
        if (! $this->isConfirmedPurchase($order, $client)) {
            return collect();
        }

        return $order->items()
            ->with('product')
            ->get()
            ->map(fn ($item): ?array => $this->describeProductItem($item, $client))
            ->filter()
            ->values();
    }

    /**
     * Producteurs de CETTE commande que ce client peut encore noter.
     *
     * @return Collection<int, array>
     */
    public function reviewableProducersFor(Order $order, User $client): Collection
    {
        if (! $this->isConfirmedPurchase($order, $client)) {
            return collect();
        }

        $producerIds = $order->items()->pluck('producer_id')->filter()->unique()->values();

        return $producerIds->map(function ($producerId) use ($order, $client): ?array {
            $producer = User::find($producerId);

            if (! $producer) {
                return null;
            }

            // canReview() refait l'integralite du controle cote serveur
            // (proprietaire, achat reel, pas d'auto-notation, pas de doublon).
            $check = $this->producerReviews->canReview($client, $order, $producer);

            if (! $check['eligible']) {
                return null;
            }

            $reviewedCount = $order->items->where('producer_id', $producer->id)->count();

            return [
                'id' => $producer->id,
                'nom' => $producer->name,
                'avatar' => $producer->avatar_url,
                'order_id' => $order->id,
                'reference' => $order->reference,
                'nombre_produits' => $reviewedCount,
            ];
        })->filter()->values();
    }

    /**
     * Tout ce que le client peut noter sur CETTE commande, produits et
     * producteurs confondus. Utilise par l'invitation post-achat ET par
     * l'onglet « Vos achats a evaluer » du dashboard client.
     *
     * @return array{products: Collection, producers: Collection, total: int}
     */
    public function invitationFor(Order $order, User $client): array
    {
        $products = $this->reviewableProductsFor($order, $client);
        $producers = $this->reviewableProducersFor($order, $client);

        return [
            'products' => $products,
            'producers' => $producers,
            'total' => $products->count() + $producers->count(),
        ];
    }

    /**
     * Tous les achats eligibles du client, tous ordres confondus, pour
     * retrouver plus tard ce qu'il n'a pas note (« Plus tard »).
     *
     * @return Collection<int, array>
     */
    public function pendingForClient(User $client): Collection
    {
        if ($client->role !== 'client') {
            return collect();
        }

        return Order::query()
            ->where('client_id', $client->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereIn('status', self::PURCHASED_STATUSES)
                  ->orWhereHas('payments', fn ($p) => $p->where('status', 'paid'));
            })
            ->with('items.product')
            ->orderByDesc('id')
            ->get()
            ->map(function (Order $order) use ($client): ?array {
                $invitation = $this->invitationFor($order, $client);

                if ($invitation['total'] === 0) {
                    return null;
                }

                return [
                    'order_id' => $order->id,
                    'reference' => $order->reference,
                    'date' => ($order->placed_at ?? $order->created_at)?->format('d/m/Y'),
                    'products' => $invitation['products'],
                    'producers' => $invitation['producers'],
                    'total' => $invitation['total'],
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Nombre d'achats encore a evaluer (badge du dashboard client).
     */
    public function pendingCountFor(User $client): int
    {
        return $this->pendingForClient($client)->sum('total');
    }

    /**
     * Description d'une ligne de commande notifiable (produit).
     */
    private function describeProductItem($item, User $client): ?array
    {
        $product = $item->product;

        // Produit supprime du catalogue : rien a noter, on ne casse pas la page.
        if (! $product) {
            return null;
        }

        // Un produit deja note n'est plus propose.
        if ($this->reviews->hasAlreadyReviewed($client, $product)) {
            return null;
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'nom' => $item->product_name,
            'image' => $item->product?->image_url,
            'producteur' => $product->producer?->name,
            'quantite' => (int) $item->quantity,
            'order_id' => $item->order_id,
            'reference' => $item->order?->reference,
        ];
    }
}