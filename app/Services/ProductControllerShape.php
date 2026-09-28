<?php

namespace App\Services;

use App\Http\Controllers\ProductController;
use App\Models\Product;

/**
 * Thin adapter so services can reuse the exact legacy catalog array shape
 * without depending on the controller directly everywhere.
 */
class ProductControllerShape
{
    /**
     * @return array<string, mixed>
     */
    public static function catalog(Product $product): array
    {
        return ProductController::toCatalogArray($product);
    }
}
