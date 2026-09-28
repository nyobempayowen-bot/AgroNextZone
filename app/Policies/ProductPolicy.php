<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Bloc B — Product (offer) authorization.
 *
 * A Producer may only toggle, update or delete HIS OWN offers.
 */
class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return (int) $product->producer_id === (int) $user->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
