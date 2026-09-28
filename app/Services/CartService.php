<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for the persistent client cart.
 *
 * Rules enforced here, whatever the caller sends:
 * - a cart always belongs to the authenticated user;
 * - quantities are integers >= 1 and never exceed the MySQL stock;
 * - the price NEVER comes from the request, it is always read from products;
 * - only available/published products with stock can be added.
 */
class CartService
{
    /**
     * Legacy session key kept only to drain the old session-based cart.
     */
    public const LEGACY_SESSION_KEY = 'cart';

    /**
     * Guard: the cart is strictly reserved to client accounts.
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
     * Return the cart of the given user, creating it on first access.
     */
    public function cartFor(User $user): Cart
    {
        $this->assertClient($user);

        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Maximum quantity allowed for a product (its MySQL stock).
     */
    public function maxQuantity(Product $product): int
    {
        return max(1, (int) $product->stock_quantity);
    }

    /**
     * Resolve a product by slug, rejecting unknown or non purchasable products.
     */
    public function findPurchasableProduct(string $slug, bool $lock = false): Product
    {
        $query = Product::query()->where('slug', $slug);

        if ($lock) {
            $query->lockForUpdate();
        }

        $product = $query->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'produit' => 'Ce produit n\'existe pas ou n\'est plus au catalogue.',
            ]);
        }

        if (! $product->isPurchasable()) {
            throw ValidationException::withMessages([
                'produit' => 'Ce produit n\'est plus disponible pour le moment.',
            ]);
        }

        return $product;
    }

    /**
     * Add a product to the cart, or increment it when it is already there.
     */
    public function add(User $user, string $slug, int $quantity = 1): CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantite' => 'La quantité doit être un nombre entier supérieur ou égal à 1.',
            ]);
        }

        return DB::transaction(function () use ($user, $slug, $quantity) {
            $cart = $this->cartFor($user);
            $product = $this->findPurchasableProduct($slug, lock: true);
            $max = $this->maxQuantity($product);

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $target = ($item?->quantity ?? 0) + $quantity;

            if ($target > $max) {
                throw ValidationException::withMessages([
                    'quantite' => "Quantité indisponible : il ne reste que {$max} unité(s) de {$product->name}.",
                ]);
            }

            if ($item) {
                $item->quantity = $target;
                $item->save();

                return $item;
            }

            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $target,
            ]);
        });
    }

    /**
     * Set an explicit quantity for a product already present in the cart.
     */
    public function setQuantity(User $user, string $slug, int $quantity): ?CartItem
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantite' => 'La quantité doit être un nombre entier supérieur ou égal à 1.',
            ]);
        }

        return DB::transaction(function () use ($user, $slug, $quantity) {
            $cart = $this->cartFor($user);
            $product = $this->findPurchasableProduct($slug, lock: true);
            $max = $this->maxQuantity($product);

            if ($quantity > $max) {
                throw ValidationException::withMessages([
                    'quantite' => "Quantité indisponible : il ne reste que {$max} unité(s) de {$product->name}.",
                ]);
            }

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                return null;
            }

            $item->quantity = $quantity;
            $item->save();

            return $item;
        });
    }

    /**
     * Remove one product line from the cart.
     */
    public function remove(User $user, string $slug): bool
    {
        $cart = $this->cartFor($user);

        $productId = Product::query()->where('slug', $slug)->value('id');

        if (! $productId) {
            return false;
        }

        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    /**
     * Empty the cart without deleting the cart row itself.
     */
    public function clear(User $user): int
    {
        $cart = $this->cartFor($user);

        return CartItem::query()->where('cart_id', $cart->id)->delete();
    }

    /**
     * Cart lines with their product, ready for Blade.
     */
    public function items(User $user): Collection
    {
        return $this->cartFor($user)
            ->items()
            ->with(['product.producer', 'product.location', 'product.images'])
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null)
            ->values();
    }

    /**
     * Server-side total, computed from the products table only.
     */
    public function subtotal(User $user): float
    {
        return $this->cartFor($user)->subtotal();
    }

    /**
     * Total number of units in the cart.
     */
    public function count(User $user): int
    {
        return (int) $this->cartFor($user)->items()->sum('quantity');
    }

    /**
     * Blade contract kept identical to the legacy session cart shape.
     *
     * @return array<string, array<string, mixed>>
     */
    public function toViewItems(User $user): array
    {
        return $this->items($user)
            ->mapWithKeys(function (CartItem $item): array {
                $product = $item->product;
                $catalog = ProductControllerShape::catalog($product);

                return [$product->slug => [
                    'slug' => $catalog['slug'],
                    'nom' => $catalog['nom'],
                    'prix' => $catalog['prix'],
                    'prix_num' => $catalog['prix_num'],
                    'image' => $catalog['image'],
                    'producteur' => $catalog['producteur'],
                    'region' => $catalog['region'],
                    'quantite' => (int) $item->quantity,
                    'stock' => (int) $product->stock_quantity,
                ]];
            })
            ->all();
    }

    /**
     * Progressive migration: import the legacy session cart once, then drop it.
     */
    public function importLegacySessionCart(User $user, array $legacyCart): int
    {
        if ($legacyCart === []) {
            return 0;
        }

        $imported = 0;

        foreach ($legacyCart as $slug => $line) {
            if (! is_string($slug) || ! is_array($line)) {
                continue;
            }

            $quantity = filter_var($line['quantite'] ?? 1, FILTER_VALIDATE_INT);

            if ($quantity === false || $quantity < 1) {
                continue;
            }

            try {
                $this->add($user, $slug, min($quantity, (int) ($line['quantite'] ?? 1)));
                $imported++;
            } catch (ValidationException) {
                // Product gone, unavailable or stock exceeded: skip this legacy line.
                continue;
            }
        }

        return $imported;
    }
}
