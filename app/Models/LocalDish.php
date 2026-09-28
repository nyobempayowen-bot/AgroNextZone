<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalDish extends Model
{
    protected $fillable = ['name', 'region', 'description'];

    public function ingredients(): HasMany
    {
        return $this->hasMany(LocalDishIngredient::class);
    }
}

class LocalDishIngredient extends Model
{
    protected $fillable = ['local_dish_id', 'ingredient_name', 'product_category_id', 'is_optional'];

    protected $casts = ['is_optional' => 'boolean'];

    public function dish(): BelongsTo
    {
        return $this->belongsTo(LocalDish::class, 'local_dish_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }
}
