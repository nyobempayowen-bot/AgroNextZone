<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Total computed server-side, from the products table only.
     */
    public function subtotal(): float
    {
        return (float) $this->items()
            ->join('products', 'products.id', '=', 'cart_items.product_id')
            ->selectRaw('COALESCE(SUM(products.price * cart_items.quantity), 0) AS subtotal')
            ->value('subtotal');
    }
}
