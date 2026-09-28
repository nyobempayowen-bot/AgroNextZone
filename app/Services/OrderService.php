<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for persistent MySQL orders.
 *
 * Rules enforced here:
 * - an order always belongs to the authenticated client;
 * - line prices are SNAPSHOTTED from the products table at creation time,
 *   so future product price changes never rewrite history;
 * - the request never supplies any price or total;
 * - the order is linked to the MySQL cart it was created from;
 * - visibility: a client sees only his orders, a producer only the orders
 *   containing at least one of his products (IDOR-safe).
 */
class OrderService
{
    /**
     * Legacy session keys kept only to drain the old array-based orders.
     */
    public const LEGACY_SESSION_KEYS = ['orders', 'producer_orders'];

    /**
     * Flat shipping fee, same value the legacy checkout used.
     */
    public const SHIPPING_FEE = 1000;

    /**
     * French labels for the Order::STATUSES stored in MySQL.
     */
    public const STATUS_LABELS = [
        'pending' => 'En attente',
        'confirmed' => 'Validée',
        'preparing' => 'En préparation',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];

    /**
     * Guard: orders are created only from client accounts.
     */
    public function assertClient(?User $user): User
    {
        if (! $user) {
            abort(401);
        }

        if ($user->role !== 'client') {
            abort(403);
        }

        return $user;
    }

    /**
     * Create a persistent order from the client's MySQL cart.
     *
     * @param  array{nom:string,telephone:string,adresse:string,moyen_paiement:string}  $shipping
     */
    public function placeOrder(User $client, array $shipping): Order
    {
        $this->assertClient($client);

        return DB::transaction(function () use ($client, $shipping) {
            $cart = Cart::query()->where('user_id', $client->id)->first();

            $items = CartItem::query()
                ->where('cart_id', $cart?->id ?? 0)
                ->with('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (CartItem $item) => $item->product !== null);

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'panier' => 'Votre panier doit contenir au moins un produit valide.',
                ]);
            }

            $subtotal = 0.0;

            $order = Order::create([
                'client_id' => $client->id,
                'status' => 'preparing', // "En préparation" — same as the legacy checkout.
                'reference' => $this->nextReference(),
                'payment_method' => $shipping['moyen_paiement'],
                'subtotal' => 0, // recomputed below before the final save.
                'shipping_fee' => self::SHIPPING_FEE,
                'total' => 0,
                'shipping_name' => $shipping['nom'],
                'shipping_phone' => $shipping['telephone'],
                'shipping_address' => $shipping['adresse'],
                'placed_at' => now(),
            ]);

            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::query()->where('id', $item->product_id)->lockForUpdate()->first();

                if (! $product) {
                    throw ValidationException::withMessages([
                        'panier' => 'Un produit du panier n\'existe plus ou n\'est plus au catalogue.',
                    ]);
                }

                $quantity = max(1, (int) $item->quantity);

                // Mission 10 — transactional stock: the reservation is atomic
                // and race-safe. The guarded decrement only succeeds when the
                // CURRENT stock still covers the quantity, so:
                // - the stock can never go negative;
                // - two concurrent checkouts cannot both take the same units;
                // - the deduction happens exactly once, here, in the same
                //   transaction as the order creation.
                $reserved = Product::query()
                    ->where('id', $product->id)
                    ->where('stock_quantity', '>=', $quantity)
                    ->decrement('stock_quantity', $quantity);

                if ($reserved === 0) {
                    throw ValidationException::withMessages([
                        'panier' => "Stock insuffisant : il ne reste que " . max(0, (int) $product->stock_quantity) . " unité(s) de {$product->name}.",
                    ]);
                }

                $unitPrice = (float) $product->price; // Price from MySQL, never from the request.
                $lineTotal = $unitPrice * $quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'producer_id' => $product->producer_id,
                    'product_name' => $product->name, // historical snapshot
                    'unit_price' => $unitPrice,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineTotal;
            }

            $order->subtotal = round($subtotal, 2);
            $order->total = round($subtotal + self::SHIPPING_FEE, 2);
            $order->save();

            // The order has been created: the cart it came from is emptied.
            CartItem::query()->where('cart_id', $cart->id)->delete();

            // Bloc C (2/2): le contexte d'achat vient de changer — on invalide
            // le cache de recommandations IA du client pour qu'il soit recalculé.
            \App\Services\GeminiRecommendationService::forgetFor($client->id);

            return $order->refresh()->load('items.product');
        });
    }

    /**
     * Unique human-readable reference: CMD-YYYY-#### (MySQL-backed uniqueness).
     */
    public function nextReference(): string
    {
        do {
            $reference = 'CMD-' . now()->format('Y') . '-' . random_int(1000, 9999);
        } while (Order::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * The client's own orders only (IDOR-safe by construction).
     */
    public function ordersForClient(User $user): Collection
    {
        return Order::query()
            ->where('client_id', $user->id)
            ->with(['items.product.images', 'items.product.producer'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Only the orders containing at least one product of this producer.
     */
    public function ordersForProducer(User $producer): Collection
    {
        return Order::query()
            ->whereHas('items', fn ($query) => $query->where('producer_id', $producer->id))
            ->with(['items.product.images', 'client'])
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get()
            ->each(fn (Order $order) => $order->setRelation(
                'items',
                $order->items->filter(fn (OrderItem $item) => (int) $item->producer_id === (int) $producer->id)->values()
            ));
    }

    /**
     * IDOR guard: a producer may touch an order only when one of his
     * products is part of it.
     */
    public function assertProducerOwnsItem(User $producer, Order $order): void
    {
        $owns = $order->items()->where('producer_id', $producer->id)->exists();

        if (! $owns) {
            abort(403, 'Cette commande ne contient aucun de vos produits.');
        }
    }

    /**
     * Update the status of an order, producer-side (IDOR-safe).
     *
     * @throws ValidationException on unknown status label
     */
    public function updateStatus(User $producer, Order $order, string $frenchLabel): Order
    {
        $this->assertProducerOwnsItem($producer, $order);

        $status = array_search($frenchLabel, self::STATUS_LABELS, true);

        if ($status === false || ! in_array($status, Order::STATUSES, true)) {
            throw ValidationException::withMessages([
                'statut' => 'Statut de commande inconnu.',
            ]);
        }

        $order->status = $status;
        $order->save();

        return $order;
    }

    /**
     * Blade contract for the client dashboard — identical keys to the
     * legacy session order arrays.
     *
     * @return array<string, mixed>
     */
    public function toClientView(Order $order): array
    {
        return [
            'id' => $order->reference,
            'order_model_id' => $order->id,
            'created_at' => $order->placed_at?->format('d/m/Y H:i') ?? $order->created_at->format('d/m/Y H:i'),
            'total' => (float) $order->total,
            'statut' => self::STATUS_LABELS[$order->status] ?? $order->status,
            'statut_step' => match ($order->status) {
                'pending' => 1,
                'confirmed', 'preparing' => 2,
                'shipped' => 3,
                'delivered' => 4,
                default => 1,
            },
            'nom' => $order->shipping_name,
            'telephone' => $order->shipping_phone,
            'adresse' => $order->shipping_address,
            'moyen_paiement' => $order->payment_method,
            'order_id' => $order->id,
            'producteur' => $order->items->first()?->product?->producer?->name ?? 'Producteur Partenaire',
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'nom' => $item->product_name,
                'prix_num' => (float) $item->unit_price,
                'quantite' => (int) $item->quantity,
                'image' => $item->product?->image_url,
            ])->all(),
        ];
    }

    /**
     * Blade contract for the producer dashboard — identical keys to the
     * legacy session producer_orders arrays.
     *
     * @return array<string, mixed>
     */
    public function toProducerView(Order $order): array
    {
        $items = $order->items; // already scoped to this producer by ordersForProducer()

        return [
            'id' => $order->reference,
            'order_id' => $order->id,
            'date' => $order->placed_at?->format('d/m/Y H:i') ?? $order->created_at->format('d/m/Y H:i'),
            'client_nom' => $order->client?->name ?? $order->shipping_name,
            'client_phone' => $order->shipping_phone,
            'client_adresse' => $order->shipping_address,
            'produit' => $items->first()?->product_name ?? 'Produit agricole',
            'quantite' => $items->sum('quantity') . ' article(s)',
            'montant' => number_format((float) $order->total, 0, ',', ' ') . ' FCFA',
            'montant_num' => (float) $order->total,
            'statut' => self::STATUS_LABELS[$order->status] ?? $order->status,
            'paiement' => ($order->payment_method ?? 'Mobile Money') . ' (Validé)',
        ];
    }
}
