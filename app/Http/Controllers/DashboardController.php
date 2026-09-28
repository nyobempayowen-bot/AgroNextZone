<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\OrderService;
use App\Services\ReviewService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Producer Dashboard Portal with all management tabs.
     */
    public function producerDashboard(Request $request): View
    {
        $user = Auth::user();
        $producerProfile = $user->producerProfile;
        $primaryLocation = $user->locations()->where('is_primary', true)->first();

        $products = Product::query()
            ->with(['producer', 'category', 'location', 'reviews', 'images'])
            ->where('producer_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Product $product): array => ProductController::toCatalogArray($product))
            ->all();
        // Orders: persistent MySQL rows — only orders containing this producer's products.
        $orderService = app(OrderService::class);
        $orderModels = $orderService->ordersForProducer($user);
        $orders = $orderModels->map(
            fn (Order $order): array => $orderService->toProducerView($order)
        )->all();
        // Mission 16 — transactions from real sales, straight from MySQL.
        $transactionService = app(TransactionService::class);
        $transactions = $transactionService->forProducer($user);
        // Mission 15 — persistent MySQL notifications (never session arrays).
        $notificationService = app(NotificationService::class);
        $notifications = $notificationService->forUser($user);
        $solde = $transactionService->revenue($user);

        $producerScore = app(ReviewService::class)->producerScore($user);

        $productRatings = collect($products)->filter(fn (array $product) => isset($product['note']))->pluck('note');
        $reliability = [
            'score_global' => $productRatings->isNotEmpty() ? round($productRatings->avg() * 20) : null,
            'ponctualite' => null,
            'conformite_produits' => null,
            'taux_reponse_chat' => null,
            'delai_moyen_reponse' => null,
            'commandes_reussies' => null,
            'badge' => $user->is_verified ? 'Producteur vérifié' : null,
        ];

        $revenue = collect($orders)->sum(fn (array $order) => (float) ($order['montant_num'] ?? 0));
        $pendingOrders = collect($orders)->filter(fn (array $order) => in_array($order['statut'] ?? '', ['En attente', 'En préparation'], true))->count();
        $completedOrders = collect($orders)->filter(fn (array $order) => ($order['statut'] ?? '') === 'Livrée')->count();
        $availableProducts = collect($products)->where('statut', 'En stock')->count();
        $dashboardStats = [
            'products_count' => count($products),
            'available_products' => $availableProducts,
            'orders_count' => count($orders),
            'pending_orders' => $pendingOrders,
            'completed_orders' => $completedOrders,
            'revenue' => $revenue,
            'average_score' => $producerScore['average'],
            'reviews_count' => $producerScore['count'],
        ];

        $activeTab = $request->query('tab', 'overview');

        return view('dashboard.producer', [
            'user' => $user,
            'products' => $products,
            'orders' => $orders,
            'transactions' => $transactions,
            'solde' => $solde,
            'notifications' => $notifications,
            'reliability' => $reliability,
            'dashboardStats' => $dashboardStats,
            'activeTab' => $activeTab,
            'producerProfile' => $producerProfile,
            'primaryLocation' => $primaryLocation,
        ]);
    }

    /**
     * Producer: Add new product to catalog with persistent image handling.
     */
    public function addProduct(\App\Http\Requests\StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($validated['categorie'])],
            ['name' => $validated['categorie']]
        );
        $location = Location::firstOrCreate(
            [
                'user_id' => $user->id,
                'region' => $validated['region'],
            ],
            [
                'country' => 'Cameroun',
                'city' => $validated['region'],
                'is_primary' => false,
            ]
        );

        $baseSlug = Str::slug($validated['nom']);
        $slug = $baseSlug;
        $suffix = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        // Store image file if provided, or use fallback image_url
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        } elseif (!empty($validated['image_url'])) {
            $imagePath = $validated['image_url'];
        }

        $product = Product::create([
            'producer_id' => $user->id,
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => $validated['nom'],
            'slug' => $slug,
            'description' => $validated['description'],
            'price' => $validated['prix_num'],
            'unit' => $validated['unite'],
            'stock_quantity' => $validated['stock'],
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'featured_image' => $imagePath,
            'published_at' => now(),
        ]);

        // Persist into product_images table
        if ($imagePath) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $imagePath,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        return redirect()->route('producer.dashboard', ['tab' => 'products'])->with('success', 'Votre offre a été mise en ligne avec succès sur la marketplace !');
    }

    /**
     * Producer: Toggle product availability status.
     */
    public function toggleProductStatus(string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        \Illuminate\Support\Facades\Gate::authorize('update', $product);
        $product->update(['is_available' => !$product->is_available]);

        return redirect()->route('producer.dashboard', ['tab' => 'products'])->with('success', 'Statut du produit actualisé.');
    }

    /**
     * Producer: Update an offer in the shared catalog.
     */
    public function updateProduct(\App\Http\Requests\UpdateProductRequest $request, string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        \Illuminate\Support\Facades\Gate::authorize('update', $product);

        $validated = $request->validated();
        $category = Category::firstOrCreate(
            ['slug' => Str::slug($validated['categorie'])],
            ['name' => $validated['categorie']]
        );
        $location = Location::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'region' => $validated['region'],
            ],
            [
                'country' => 'Cameroun',
                'city' => $validated['region'],
                'is_primary' => false,
            ]
        );

        $updateData = [
            'category_id' => $category->id,
            'location_id' => $location->id,
            'name' => $validated['nom'],
            'price' => $validated['prix_num'],
            'unit' => $validated['unite'],
            'stock_quantity' => $validated['stock'],
            'description' => $validated['description'],
            'is_available' => $validated['stock'] > 0,
        ];

        // Handle image replacement/update if provided
        $newImagePath = null;
        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('products', 'public');
        } elseif (!empty($validated['image_url'])) {
            $newImagePath = $validated['image_url'];
        }

        if ($newImagePath) {
            // Delete old primary local file to avoid orphaned storage files
            $oldPrimary = $product->images()->where('is_primary', true)->first();
            if ($oldPrimary) {
                if (!Str::startsWith($oldPrimary->image_path, ['http://', 'https://']) && Storage::disk('public')->exists($oldPrimary->image_path)) {
                    Storage::disk('public')->delete($oldPrimary->image_path);
                }
                $oldPrimary->delete();
            }

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $newImagePath,
                'is_primary' => true,
                'sort_order' => 1,
            ]);

            $updateData['featured_image'] = $newImagePath;
        }

        $product->update($updateData);

        return redirect()->route('producer.dashboard', ['tab' => 'products'])->with('success', 'Votre offre a été modifiée.');
    }

    /**
     * Producer: Remove an offer and clean up its stored images.
     */
    public function deleteProduct(string $slug): RedirectResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        \Illuminate\Support\Facades\Gate::authorize('delete', $product);

        // Clean up stored image files
        foreach ($product->images as $img) {
            if (!Str::startsWith($img->image_path, ['http://', 'https://']) && Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        $product->delete();

        return redirect()->route('producer.dashboard', ['tab' => 'products'])->with('success', 'Votre offre a été supprimée.');
    }

    /**
     * Producer: Update status of a received order (MySQL, IDOR-safe).
     */
    public function updateOrderStatus(Request $request, string $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'statut' => ['required', 'string'],
        ]);

        $order = Order::query()->where('reference', $orderId)->firstOrFail();
        \Illuminate\Support\Facades\Gate::authorize('updateStatus', $order);

        app(OrderService::class)->updateStatus(Auth::user(), $order, $validated['statut']);

        // Mission 15 — persistent notification for the client on order status change.
        if ($order->client) {
            app(NotificationService::class)->send(
                $order->client,
                'order_status',
                'Commande mise à jour',
                "Votre commande {$order->reference} est maintenant : {$validated['statut']}."
            );
        }

        return redirect()->route('producer.dashboard', ['tab' => 'orders'])->with('success', 'Statut de la commande mis à jour : ' . $validated['statut']);
    }

    /**
     * Producer: Request a Mobile Money payout withdrawal.
     */
    public function withdraw(Request $request): RedirectResponse
    {
        $amount = (int) $request->input('montant', 0);
        $phone = $request->input('telephone', '');
        $transactionService = app(TransactionService::class);
        $currentBalance = $transactionService->revenue(Auth::user());

        if ($amount <= 0 || $amount > $currentBalance) {
            return redirect()->route('producer.dashboard', ['tab' => 'transactions'])->with('error', 'Montant de retrait invalide ou solde insuffisant.');
        }

        // Mission 16 — the withdrawal is a persistent, typed transaction row.
        $transactionService->recordWithdrawal(Auth::user(), (float) $amount, $request->input('operateur', 'MTN Mobile Money'));

        return redirect()->route('producer.dashboard', ['tab' => 'transactions'])->with('success', 'Demande de retrait de ' . number_format($amount, 0, ',', ' ') . ' FCFA enregistrée.');
    }

    /**
     * Producer: Mark all notifications as read.
     */
    public function markProducerNotificationsRead(): RedirectResponse
    {
        // Mission 15 — persistent MySQL read state, scoped to this user only.
        app(NotificationService::class)->markAllRead(Auth::user());

        return redirect()->route('producer.dashboard', ['tab' => 'notifications'])->with('success', 'Toutes les notifications sont marquées comme lues.');
    }

    /**
     * Producer: Update account settings and farm info.
     */
    public function updateProducerAccount(\App\Http\Requests\UpdateProducerAccountRequest $request): RedirectResponse
    {
        $user = Auth::user();
        \Illuminate\Support\Facades\Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'] ?? $user->name;
        $user->phone = $validated['phone'] ?? $user->phone;
        $description = $validated['description'] ?? $validated['bio'] ?? $user->bio;
        $user->bio = $description;
        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('profile_photos', 'public');
        }
        $user->save();

        $user->producerProfile()->updateOrCreate([], [
            'activity_type' => $validated['activity_type'] ?? null,
            'specialty' => $validated['specialty'] ?? null,
            'main_products' => $validated['main_products'] ?? null,
            'farm_name' => $validated['farm_name'] ?? null,
            'years_experience' => $validated['years_experience'] ?? null,
            'description' => $description,
        ]);

        if (!empty($validated['farm_location'])) {
            $user->locations()->where('is_primary', true)->first()?->update([
                'address' => $validated['farm_location'],
            ]);
        }

        // Bloc C — re-geocode the producer's address in the background of the
        // request; failure never blocks the save (coordinates stay null).
        try {
            app(GeocodingService::class)->geocodeProducer($user->refresh());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Producer geocoding skipped', ['error' => $e->getMessage()]);
        }

        return redirect()->route('producer.dashboard', ['tab' => 'account'])->with('success', 'Vos informations de compte ont été enregistrées.');
    }

    /**
     * Client Dashboard Portal with orders timeline, transactions, notifications, and account.
     */
    public function clientDashboard(Request $request): View
    {
        $user = Auth::user();

        // 1. Client orders: persistent MySQL rows, never session arrays.
        $orderService = app(OrderService::class);
        $producerReviewService = app(\App\Services\ProducerReviewService::class);
        $orders = $orderService->ordersForClient($user)->map(
            function (Order $order) use ($orderService, $producerReviewService, $user): array {
                $view = $orderService->toClientView($order);

                // Notation producteur : le producteur principal de la commande.
                $producer = $order->items->first()?->product?->producer;
                $view['producteur_id'] = $producer?->id;

                // Avis déjà laissé par ce client pour cette commande (affichage).
                $existing = $producer
                    ? \App\Models\ProducerReview::query()
                        ->where('order_id', $order->id)
                        ->where('client_id', $user->id)
                        ->first()
                    : null;
                $view['mon_avis'] = $existing ? [
                    'id' => $existing->id,
                    'note' => (int) $existing->rating,
                    'commentaire' => (string) $existing->comment,
                    'date' => $existing->created_at->format('d/m/Y'),
                ] : null;

                if ($producer) {
                    $eligibility = $producerReviewService->canReview($user, $order, $producer);
                    $view['peut_noter'] = $eligibility['eligible'];
                    $view['raison_non_notation'] = $view['mon_avis'] ? null : $eligibility['reason'];
                } else {
                    $view['peut_noter'] = false;
                    $view['raison_non_notation'] = null;
                }

                return $view;
            }
        )->all();

        // Mission 16 — the client's payment history comes from the MySQL payments table.
        $transactions = \App\Models\Payment::query()
            ->where('client_id', $user->id)
            ->with('order')
            ->orderByDesc('id')
            ->get()
            ->map(fn (\App\Models\Payment $payment): array => [
                'id' => $payment->provider_reference ?? ('PAY-' . $payment->id),
                'date' => $payment->created_at->format('d/m/Y H:i'),
                'commande_id' => $payment->order?->reference ?? '-',
                'montant' => number_format((float) $payment->amount, 0, ',', ' ') . ' FCFA',
                'methode' => ucfirst(str_replace('_', ' ', $payment->method)),
                'statut' => match ($payment->status) {
                    'paid' => 'Payé avec succès',
                    'pending' => 'En attente',
                    'failed' => 'Échoué',
                    'cancelled' => 'Annulé',
                    default => $payment->status,
                },
            ])->all();

        // 3. Mission 15 — persistent MySQL notifications.
        $notifications = app(NotificationService::class)->forUser($user);

        $activeTab = $request->query('tab', 'orders');

        // Persistent MySQL cart: counters and totals are read from the database.
        $cart = $user->cart;
        $cartCount = (int) ($cart?->items()->sum('quantity') ?? 0);
        $cartTotal = (float) ($cart?->subtotal() ?? 0);
        $ordersCount = count($orders);

        // Recommendations for client (only available when logged in)
        $recommendations = [
            ['nom' => 'Cacao fermenté qualité supérieure', 'prix' => '2 400 FCFA/kg'],
            ['nom' => 'Régime de Plantain Doux', 'prix' => '3 500 FCFA/régime'],
            ['nom' => 'Avocat Beurre Hass Frais', 'prix' => '1 200 FCFA/kg'],
        ];

        return view('dashboard.client', [
            'user' => $user,
            'orders' => $orders,
            'transactions' => $transactions,
            'notifications' => $notifications,
            'cartCount' => $cartCount,
            'cartTotal' => $cartTotal,
            'ordersCount' => $ordersCount,
            'recommendations' => $recommendations,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Client: Mark notifications as read.
     */
    public function markClientNotificationsRead(): RedirectResponse
    {
        // Mission 15 — persistent MySQL read state, scoped to this user only.
        app(NotificationService::class)->markAllRead(Auth::user());

        return redirect()->route('client.dashboard', ['tab' => 'notifications'])->with('success', 'Notifications marquées comme lues.');
    }

    /**
     * Client: Update account details.
     */
    public function updateClientAccount(\App\Http\Requests\UpdateClientAccountRequest $request): RedirectResponse
    {
        $user = Auth::user();
        \Illuminate\Support\Facades\Gate::authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->phone = $validated['phone'];
        $user->adresse = $validated['adresse'] ?? $user->adresse;
        $user->region = $validated['region'] ?? $user->region;
        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('profile_photos', 'public');
        }

        // Handle password change if provided
        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return redirect()->route('client.dashboard', ['tab' => 'account'])->with('error', 'Mot de passe actuel incorrect.');
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->route('client.dashboard', ['tab' => 'account'])->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Admin Dashboard.
     */
    public function adminDashboard(): View
    {
        $stats = $this->adminService->stats();

        return view('dashboard.admin', [
            'user' => Auth::user(),
            'stats' => $stats,
        ]);
    }
}
