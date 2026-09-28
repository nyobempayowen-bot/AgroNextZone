<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'producer_id',
        'category_id',
        'location_id',
        'name',
        'slug',
        'description',
        'price',
        'unit',
        'stock_quantity',
        'minimum_order',
        'is_available',
        'status',
        'featured_image',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'minimum_order' => 'integer',
            'is_available' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): string
    {
        // 1. Image marquée principale dans product_images
        $primary = $this->images->firstWhere('is_primary', true);
        if ($primary && !empty($primary->url)) {
            return $primary->url;
        }

        // 2. Première image associée
        $firstImage = $this->images->first();
        if ($firstImage && !empty($firstImage->url)) {
            return $firstImage->url;
        }

        // 3. Fallback sur featured_image (pour compatibilité avec données existantes)
        if (!empty($this->featured_image)) {
            if (Str::startsWith($this->featured_image, ['http://', 'https://', '/'])) {
                return $this->featured_image;
            }
            if (Storage::disk('public')->exists($this->featured_image)) {
                return asset('storage/' . ltrim($this->featured_image, '/'));
            }
            return asset('storage/' . ltrim($this->featured_image, '/'));
        }

        // 4. Fallback par défaut garanti
        return 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80';
    }

    public function producer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * A product is purchasable only when available, published and in stock.
     */
    public function isPurchasable(): bool
    {
        return $this->is_available
            && $this->status === 'published'
            && (int) $this->stock_quantity > 0;
    }

    /**
     * Published reviews only: the rating and the review count shown on the
     * catalog and on the product page must never include hidden, pending or
     * rejected reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'published');
    }

    /** Signalements déposés sur cette offre (modération admin). */
    public function reports(): HasMany
    {
        return $this->hasMany(ProductReport::class);
    }
}
