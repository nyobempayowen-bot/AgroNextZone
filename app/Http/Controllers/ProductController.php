<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ReviewService;

class ProductController extends Controller
{
    /**
     * Default catalog of Cameroon agricultural products.
     */
    public static function getDefaultProducts(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'cacao-fermente-qualite-superieure',
                'nom' => 'Cacao fermenté qualité supérieure',
                'categorie' => 'Cacao & Café',
                'prix' => '2 400 FCFA',
                'prix_num' => 2400,
                'prix_barre' => '2 800 FCFA',
                'unite' => 'kg',
                'stock' => 850,
                'statut' => 'En stock',
                'region' => 'Centre (Bafia)',
                'producteur' => 'Nkwenti Agric & Fils',
                'producteur_id' => 1,
                'badge' => 'Choix AgroNextZone',
                'note' => 4.9,
                'avis_count' => 38,
                'image' => 'https://images.unsplash.com/photo-1602192103205-c3836b86696d?auto=format&fit=crop&w=900&q=80',
                'description' => 'Fèves de cacao rigoureusement fermentées et séchées au soleil. Taux d\'humidité optimal (< 7.5%), arôme boisé puissant et traçabilité 100% garantie depuis nos plantations de Bafia.',
                'caracteristiques' => [
                    'Origine' => 'Bafia, Région du Centre',
                    'Certification' => 'Agro-Écologique',
                    'Conditionnement' => 'Sacs en jute de 50 kg ou au détail',
                    'Livraison estimée' => '24h - 48h partout au Cameroun'
                ],
            ],
            [
                'id' => 2,
                'slug' => 'plantain-doux-ebolowa',
                'nom' => 'Régime de Plantain Doux Gros Calibre',
                'categorie' => 'Vivriers & Tubercules',
                'prix' => '3 500 FCFA',
                'prix_num' => 3500,
                'prix_barre' => '4 200 FCFA',
                'unite' => 'régime',
                'stock' => 120,
                'statut' => 'En stock',
                'region' => 'Sud (Ebolowa)',
                'producteur' => 'Plantations Mireille N.',
                'producteur_id' => 2,
                'badge' => 'Direct Producteur',
                'note' => 4.8,
                'avis_count' => 52,
                'image' => 'https://images.unsplash.com/photo-1601493700631-2b16ec4b4716?auto=format&fit=crop&w=900&q=80',
                'description' => 'Régimes de plantains bien charnus, récoltés à maturité optimale à Ebolowa. Idéaux pour le tapé, friture ou cuisson à la vapeur pour familles et restaurants.',
                'caracteristiques' => [
                    'Origine' => 'Ebolowa, Région du Sud',
                    'Poids moyen' => '15 à 22 kg / régime',
                    'Mode de culture' => 'Zéro pesticide chimique',
                    'Livraison estimée' => 'Même jour sur Yaoundé & Douala'
                ],
            ],
            [
                'id' => 3,
                'slug' => 'avocat-beurre-hass-buea',
                'nom' => 'Avocat Beurre Hass Frais des Montagnes',
                'categorie' => 'Fruits & Légumes',
                'prix' => '1 200 FCFA',
                'prix_num' => 1200,
                'prix_barre' => '1 500 FCFA',
                'unite' => 'kg',
                'stock' => 300,
                'statut' => 'En stock',
                'region' => 'Sud-Ouest (Buea)',
                'producteur' => 'Ferme des Flancs du Mont Fako',
                'producteur_id' => 1,
                'badge' => 'Meilleure Vente',
                'note' => 4.9,
                'avis_count' => 64,
                'image' => 'https://images.unsplash.com/photo-1519162808019-7de1683fa2ad?auto=format&fit=crop&w=900&q=80',
                'description' => 'Avocats extrêmement onctueux, texture beurre et peau rugueuse. Riche en graisses saines et vitamines, récoltés dans le sol volcanique fertile de Buea.',
                'caracteristiques' => [
                    'Origine' => 'Buea, Sud-Ouest',
                    'Calibre' => 'Gros (300g+ par pièce)',
                    'Conservation' => '7 à 10 jours au frais',
                    'Livraison estimée' => 'Expédition quotidienne'
                ],
            ],
            [
                'id' => 4,
                'slug' => 'ananas-pain-de-sucre-dschang',
                'nom' => 'Ananas Pain de Sucre Ultra Sucré',
                'categorie' => 'Fruits & Légumes',
                'prix' => '800 FCFA',
                'prix_num' => 800,
                'prix_barre' => '1 000 FCFA',
                'unite' => 'pièce',
                'stock' => 240,
                'statut' => 'En stock',
                'region' => 'Ouest (Dschang)',
                'producteur' => 'Vergers Blaise T.',
                'producteur_id' => 2,
                'badge' => 'Bio Certifié',
                'note' => 4.7,
                'avis_count' => 29,
                'image' => 'https://images.unsplash.com/photo-1550258987-190a2d41a8ba?auto=format&fit=crop&w=900&q=80',
                'description' => 'Chair blanche et cœur tendre 100% consommable. Ananas pain de sucre doux sans acidité excessive, gorgé de soleil de l\'Ouest.',
                'caracteristiques' => [
                    'Origine' => 'Dschang, Ouest',
                    'Teneur en sucre' => 'Naturellement élevé (Brix 14°+)',
                    'Livraison estimée' => '24h - 48h'
                ],
            ],
            [
                'id' => 5,
                'slug' => 'arachides-grillees-ngaoundere',
                'nom' => 'Arachides du Grand Nord Grillées au Feu de Bois',
                'categorie' => 'Semences & Grains',
                'prix' => '1 500 FCFA',
                'prix_num' => 1500,
                'prix_barre' => null,
                'unite' => 'kg',
                'stock' => 180,
                'statut' => 'En stock',
                'region' => 'Adamaoua (Ngaoundéré)',
                'producteur' => 'GIE Sahel & Saveurs',
                'producteur_id' => 1,
                'badge' => 'Traditionnel',
                'note' => 4.8,
                'avis_count' => 41,
                'image' => 'https://images.unsplash.com/photo-1582515073490-39981397c445?auto=format&fit=crop&w=900&q=80',
                'description' => 'Arachides croquantes, torréfaction artisanale sans huile ajoutée. Goût authentique et parfum fumé irrésistible.',
                'caracteristiques' => [
                    'Origine' => 'Ngaoundéré, Adamaoua',
                    'Traitement' => 'Torréfaction bois traditionnelle',
                    'Livraison estimée' => '48h par colis express'
                ],
            ],
            [
                'id' => 6,
                'slug' => 'manioc-frais-maroua',
                'nom' => 'Bâtons de Manioc & Tubercules Blancs Frais',
                'categorie' => 'Vivriers & Tubercules',
                'prix' => '700 FCFA',
                'prix_num' => 700,
                'prix_barre' => '900 FCFA',
                'unite' => 'kg',
                'stock' => 450,
                'statut' => 'En stock',
                'region' => 'Extrême-Nord (Maroua)',
                'producteur' => 'Coopérative Alain M.',
                'producteur_id' => 2,
                'badge' => 'Prix Coopératif',
                'note' => 4.6,
                'avis_count' => 19,
                'image' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?auto=format&fit=crop&w=900&q=80',
                'description' => 'Manioc sélectionné avec soin, sans fibres dures, parfait pour le fufu, le couscous ou les beignets de manioc.',
                'caracteristiques' => [
                    'Origine' => 'Maroua, Extrême-Nord',
                    'Variété' => 'Manioc blanc doux',
                    'Livraison estimée' => '48h'
                ],
            ],
        ];
    }

    /**
     * Return the public catalog from MySQL while preserving the legacy view shape.
     */
    public static function getProducts(): array
    {
        return Product::query()
            ->with(['producer', 'category', 'location', 'reviews', 'images'])
            ->where('is_available', true)
            ->where('status', 'published')
            ->where('stock_quantity', '>', 0)
            ->latest('published_at')
            ->latest('id')
            ->get()
            ->map(fn (Product $product): array => self::toCatalogArray($product))
            ->all();
    }

    /**
     * Adapt an Eloquent product to the existing Blade/cart contract.
     */
    public static function toCatalogArray(Product $product): array
    {
        $price = (float) $product->price;
        $regionName = $product->location?->region ?? $product->producer?->region ?? 'Cameroun';
        $cityName = $product->location?->city;
        $regionDisplay = ($cityName && $cityName !== 'Cameroun' && $cityName !== $regionName)
            ? "{$regionName} ({$cityName})"
            : $regionName;
        $status = $product->is_available && $product->stock_quantity > 0 ? 'En stock' : 'En rupture temporaire';

        $imageUrl = $product->image_url;
        $imagesList = $product->images->map(fn ($img) => $img->url)->all();
        if (empty($imagesList)) {
            $imagesList = [$imageUrl];
        }

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'nom' => $product->name,
            'categorie' => $product->category?->name ?? 'Produit agricole',
            'prix' => number_format($price, 0, ',', ' ') . ' FCFA',
            'prix_num' => $price,
            'prix_barre' => null,
            'unite' => $product->unit,
            'stock' => $product->stock_quantity,
            'statut' => $status,
            'region' => $regionDisplay,
            'producteur' => $product->producer?->name ?? 'Producteur local',
            'producteur_id' => $product->producer_id,
            'badge' => $product->stock_quantity > 200 ? 'Direct Producteur' : null,
            // Note et nombre d'avis : uniquement des données MySQL réelles.
            // Aucun defaut fictif (4.8 / 12 avis) : un produit sans avis
            // affiche « — » et « 0 evaluations ».
            'note' => $product->reviews->isNotEmpty()
                ? round((float) $product->reviews->avg('rating'), 1)
                : null,
            'avis_count' => $product->reviews->count(),
            'image' => $imageUrl,
            'images' => $imagesList,
            'description' => $product->description ?? '',
            'caracteristiques' => [
                'Origine' => $regionDisplay,
                'Conditionnement' => 'Au détail ou par lot (' . $product->unit . ')',
                'Disponibilité' => $status,
            ],
        ];
    }

    /**
     * Display the Marketplace / Home with Amazon-like search & filters directly backed by MySQL.
     */
    public function index(Request $request): View
    {
        $queryText = trim((string) $request->input('recherche', ''));
        $category = trim((string) $request->input('categorie', ''));
        $region = trim((string) $request->input('region', ''));
        $sort = trim((string) $request->input('tri', 'populaire'));

        $eloquentQuery = Product::query()
            ->with(['producer', 'category', 'location', 'reviews', 'images'])
            ->where('is_available', true)
            ->where('status', 'published')
            ->where('stock_quantity', '>', 0);

        // Filter by keyword in SQL
        if ($queryText !== '') {
            $eloquentQuery->where(function ($q) use ($queryText) {
                $q->where('name', 'like', "%{$queryText}%")
                  ->orWhere('description', 'like', "%{$queryText}%")
                  ->orWhereHas('category', fn ($catQ) => $catQ->where('name', 'like', "%{$queryText}%"))
                  ->orWhereHas('location', fn ($locQ) => $locQ->where('region', 'like', "%{$queryText}%")->orWhere('city', 'like', "%{$queryText}%"))
                  ->orWhereHas('producer', fn ($prodQ) => $prodQ->where('name', 'like', "%{$queryText}%"));

                $words = array_filter(preg_split('/\s+/', $queryText), fn ($w) => mb_strlen($w) >= 3);
                foreach ($words as $word) {
                    $q->orWhere('name', 'like', "%{$word}%")
                      ->orWhere('description', 'like', "%{$word}%")
                      ->orWhereHas('category', fn ($catQ) => $catQ->where('name', 'like', "%{$word}%"))
                      ->orWhereHas('location', fn ($locQ) => $locQ->where('region', 'like', "%{$word}%"));
                }
            });
        }

        // Filter by category in SQL
        if ($category !== '' && $category !== 'Toutes les catégories') {
            $eloquentQuery->whereHas('category', fn ($catQ) => $catQ->where('name', $category));
        }

        // Filter by region in SQL
        if ($region !== '' && $region !== 'Toutes les régions') {
            $eloquentQuery->where(function ($q) use ($region) {
                $q->whereHas('location', fn ($locQ) => $locQ->where('region', 'like', "%{$region}%"))
                  ->orWhereHas('producer', fn ($prodQ) => $prodQ->where('region', 'like', "%{$region}%"));
            });
        }

        // Order in SQL
        if ($sort === 'prix_croissant') {
            $eloquentQuery->orderBy('price', 'asc');
        } elseif ($sort === 'prix_decroissant') {
            $eloquentQuery->orderBy('price', 'desc');
        } else {
            $eloquentQuery->latest('published_at')->latest('id');
        }

        $productsCollection = $eloquentQuery->get()->map(fn (Product $product): array => self::toCatalogArray($product));

        if ($sort === 'note') {
            $productsCollection = $productsCollection->sortByDesc('note')->values();
        }

        // Bloc C — "near me": sort the filtered results by Haversine distance
        // from the client's browser position (graceful fallback: classic order).
        $clientLat = $request->input('lat') !== null ? (float) $request->input('lat') : null;
        $clientLng = $request->input('lng') !== null ? (float) $request->input('lng') : null;

        if ($clientLat !== null && $clientLng !== null) {
            $producerCoords = \App\Models\User::query()
                ->where('role', 'producer')
                ->with('producerProfile:id,user_id,latitude,longitude')
                ->get(['id'])
                ->mapWithKeys(fn (\App\Models\User $u): array => [
                    $u->id => [
                        // Bloc C: coordinates live on producer_profiles (geocoded)
                        // or directly on users (fallback from the 2026_09_14 migration).
                        'lat' => $u->producerProfile?->latitude ?? $u->latitude,
                        'lng' => $u->producerProfile?->longitude ?? $u->longitude,
                    ],
                ]);

            $productsCollection = $productsCollection
                ->sortBy(function (array $product) use ($producerCoords, $clientLat, $clientLng): float {
                    $coords = $producerCoords[$product['producteur_id'] ?? null] ?? null;
                    if ($coords === null || $coords['lat'] === null || $coords['lng'] === null) {
                        return PHP_FLOAT_MAX; // producers without coordinates go last.
                    }

                    return self::haversineKm($clientLat, $clientLng, $coords['lat'], $coords['lng']);
                })
                ->values();
        }

        // Cart items count, read from the persistent MySQL cart (clients only).
        $cartCount = Auth::user()?->role === 'client'
            ? app(CartService::class)->count(Auth::user())
            : 0;

        // Bloc C — geolocation: unique producers of the CURRENT filtered results,
        // with coordinates when available (profile geocoding or location row).
        // Producers without coordinates stay in the text list, absent from the map.
        $mapPoints = $eloquentQuery->get()
            ->map(fn (Product $p): ?array => [
                'producer' => $p->producer?->name ?? 'Producteur local',
                'producer_id' => $p->producer_id,
                'region' => $p->location?->region ?? $p->producer?->region ?? '',
                'city' => $p->location?->city ?? '',
                'lat' => (float) ($p->producer?->producerProfile?->latitude
                    ?? $p->producer?->latitude
                    ?? $p->location?->latitude
                    ?? 0),
                'lng' => (float) ($p->producer?->producerProfile?->longitude
                    ?? $p->producer?->longitude
                    ?? $p->location?->longitude
                    ?? 0),
                'has_coords' => ($p->producer?->producerProfile?->latitude !== null
                    || $p->producer?->latitude !== null
                    || $p->location?->latitude !== null),
                'score' => $p->reviews->isNotEmpty() ? round((float) $p->reviews->avg('rating'), 1) : null,
            ])
            ->filter(fn ($point) => $point['has_coords'] && $point['lat'] != 0 && $point['lng'] != 0)
            ->unique(fn ($point) => $point['producer'].'|'.$point['lat'].'|'.$point['lng'])
            ->values()
            ->all();

        return view('Accueil', [
            'mapPoints' => $mapPoints,
            'clientLat' => $clientLat,
            'clientLng' => $clientLng,
            'products' => $productsCollection->all(),
            'cartCount' => $cartCount,
            'selectedCategory' => $category,
            'selectedRegion' => $region,
            'selectedSort' => $sort,
            'searchQuery' => $queryText,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show single product details backed directly by MySQL Eloquent.
     */
    public function show(string $slug): View
    {
        $productModel = Product::query()
            ->with(['producer', 'category', 'location', 'reviews', 'images'])
            ->where('slug', $slug)
            ->where('is_available', true)
            ->where('status', 'published')
            ->firstOrFail();

        $product = self::toCatalogArray($productModel);

        // Fetch similar products directly from MySQL/Eloquent
        $similarProducts = Product::query()
            ->with(['producer', 'category', 'location', 'reviews', 'images'])
            ->where('id', '!=', $productModel->id)
            ->where('is_available', true)
            ->where('status', 'published')
            ->where('stock_quantity', '>', 0)
            ->when($productModel->category_id, fn ($q) => $q->where('category_id', $productModel->category_id))
            ->limit(4)
            ->get()
            ->map(fn (Product $p): array => self::toCatalogArray($p))
            ->all();

        if (empty($similarProducts)) {
            $similarProducts = Product::query()
                ->with(['producer', 'category', 'location', 'reviews', 'images'])
                ->where('id', '!=', $productModel->id)
                ->where('is_available', true)
                ->where('status', 'published')
                ->where('stock_quantity', '>', 0)
                ->limit(4)
                ->get()
                ->map(fn (Product $p): array => self::toCatalogArray($p))
                ->all();
        }

        // Mission 13 — reviews come from the persistent MySQL reviews table.
        $reviewService = app(ReviewService::class);
        $reviews = $reviewService->forProduct($productModel);
        // Statistiques réelles (moyenne, nombre, répartition) — aucun defaut fictif.
        $reviewStats = $reviewService->productStats($productModel);

        // L'utilisateur connecté a-t-il déjà publié un avis sur ce produit ?
        $myReview = Auth::user()?->role === 'client'
            ? \App\Models\Review::query()
                ->where('product_id', $productModel->id)
                ->where('client_id', Auth::id())
                ->latest('id')
                ->first()
            : null;

        return view('produit', [
            'produit' => $product,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'myReview' => $myReview,
            'similarProducts' => $similarProducts,
            'user' => Auth::user(),
        ]);
    }

    /**
     * Publication d'un avis : delegates a ReviewController.
     * Conserve pour compatibilite (aucune vue ne pointe plus ici).
     */
    public function addReview(\App\Http\Requests\StoreReviewRequest $request, string $slug): RedirectResponse
    {
        return app(ReviewController::class)->store($request, $slug);
    }

    /**
     * Direct Buy button action: adds to cart and redirects straight to checkout.
     */
    public function buyNow(string $slug): RedirectResponse
    {
        $productModel = Product::query()
            ->with(['producer', 'category', 'location'])
            ->where('slug', $slug)
            ->where('is_available', true)
            ->where('status', 'published')
            ->where('stock_quantity', '>', 0)
            ->firstOrFail();

        // Persistent MySQL cart: the price is always read from the database.
        app(CartService::class)->add(Auth::user(), $productModel->slug, 1);

        return redirect()->route('checkout');
    }

    /**
     * Bloc C — great-circle distance in km between two points (Haversine).
     */
    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            * cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            + sin($dLng / 2) ** 2 * cos(deg2rad($lat1)) * cos(deg2rad($lat2));

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }
}
