<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Bloc C (2/2) — Marché des prix.
 * Agrégation SQL pure (aucun appel IA) : pour chaque produit actif,
 * prix moyen / min / max calculés à partir des offres réelles en base.
 */
class MarketPriceService
{
    /**
     * Statistiques de prix par produit (offres actives uniquement).
     *
     * @return Collection<int, array{name:string, slug:string, unit:string, category:?string, offers:int, avg:float, min:float, max:float}>
     */
    public function stats(): Collection
    {
        return DB::table('products')
            ->join('users', 'users.id', '=', 'products.producer_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.is_available', true)
            ->where('products.status', 'published')
            ->where('products.stock_quantity', '>', 0)
            ->groupBy('products.name', 'products.unit', 'categories.name')
            ->orderBy('products.name')
            ->get([
                'products.name',
                'products.unit',
                DB::raw('MIN(products.slug) AS slug'),
                DB::raw('categories.name AS category'),
                DB::raw('COUNT(*) AS offers'),
                DB::raw('ROUND(AVG(products.price), 2) AS avg_price'),
                DB::raw('MIN(products.price) AS min_price'),
                DB::raw('MAX(products.price) AS max_price'),
            ])
            ->map(fn ($r) => [
                'name' => $r->name,
                'slug' => $r->slug,
                'unit' => $r->unit,
                'category' => $r->category,
                'offers' => (int) $r->offers,
                'avg' => (float) $r->avg_price,
                'min' => (float) $r->min_price,
                'max' => (float) $r->max_price,
            ]);
    }
}
