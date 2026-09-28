<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

/**
 * Bloc B — Cart authorization & IDOR defense.
 *
 * Rules:
 * - A client may only view, update or clear HIS OWN cart.
 * - Producers and admins have NO direct access to client carts.
 */
class CartPolicy
{
    public function view(User $user, Cart $cart): bool
    {
        return (int) $cart->user_id === (int) $user->id;
    }

    public function update(User $user, Cart $cart): bool
    {
        return (int) $cart->user_id === (int) $user->id;
    }

    public function delete(User $user, Cart $cart): bool
    {
        return (int) $cart->user_id === (int) $user->id;
    }
}

