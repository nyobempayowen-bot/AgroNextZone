<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Bloc B — Order authorization.
 *
 * A Client may only view/pay HIS orders. A Producer may only change the
 * status of orders that contain HIS products. No admin back-door on the
 * payment flow (out of the Bloc B admin scope).
 */
class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return (int) $order->client_id === (int) $user->id;
    }

    public function decidePayment(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $order->items()
            ->where('producer_id', $user->id)
            ->exists();
    }
}
