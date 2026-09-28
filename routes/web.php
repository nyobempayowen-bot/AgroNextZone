<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DishAssistantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\MarketIntelligenceController;
use App\Http\Controllers\MessagingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProducerReviewController;
use App\Http\Controllers\ReviewController;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Marketplace / Home (Amazon-inspired catalog)
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/marketplace', [ProductController::class, 'index'])->name('marketplace');

// Product details, reviews & direct buy
Route::get('/produit/{slug}', [ProductController::class, 'show'])->name('produit');
// L'URL contient un slug, pas un id : le produit est résolu explicitement
// dans ReviewController::store (sinon le model binding chercherait l'id "slug").
Route::post('/produit/{slug}/avis', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('produit.avis');
// Modification / suppression d'un avis : strictement son auteur (ReviewPolicy).
Route::put('/avis/{review}', [ReviewController::class, 'update'])
    ->middleware('auth')
    ->name('avis.update');
Route::delete('/avis/{review}', [ReviewController::class, 'destroy'])
    ->middleware('auth')
    ->name('avis.destroy');
Route::post('/produit/{slug}/acheter', [ProductController::class, 'buyNow'])
    ->middleware('role:client')
    ->name('produit.acheter');

// Bloc B — legacy single-shot registration: strict server-side validation,
// the role is constrained to client|producer (no self-assigned admin).
Route::post('/register', [AuthController::class, 'register'])->name('register.store');

// Registration Step-by-Step
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register/step1', [RegisterController::class, 'step1'])->name('register.step1');
Route::post('/register/step2', [RegisterController::class, 'step2'])->name('register.step2');
Route::post('/register/step3', [RegisterController::class, 'step3'])->name('register.step3');
Route::post('/register/step4', [RegisterController::class, 'step4'])->name('register.step4');
Route::post('/register/step5', [RegisterController::class, 'step5'])->name('register.step5');
Route::post('/register/step6', [RegisterController::class, 'step6'])->name('register.step6');
Route::post('/register/send-otp', [RegisterController::class, 'sendOtp'])->name('register.sendOtp');
Route::post('/register/resend-otp', [RegisterController::class, 'resendOtp'])->name('register.resendOtp');
Route::get('/register/recap', [RegisterController::class, 'getRecap'])->name('register.recap');
Route::post('/register/step8', [RegisterController::class, 'step8'])->name('register.step8');
Route::post('/register/check-email', [RegisterController::class, 'checkEmail'])->name('register.checkEmail');
Route::post('/register/check-phone', [RegisterController::class, 'checkPhone'])->name('register.checkPhone');

// Cart Management — persistent MySQL cart (Mission 8), reserved to clients.
$cartService = fn (): CartService => app(CartService::class);

$resolveCart = function (mixed $cart) use ($cartService): array {
    $user = Auth::user();

    if (! $user) {
        return [];
    }

    // Legacy session payloads are drained into MySQL, never trusted for pricing.
    if (is_array($cart) && $cart !== []) {
        $cartService()->importLegacySessionCart($user, $cart);
    }

    return $cartService()->toViewItems($user);
};

Route::post('/panier/ajouter/{slug}', [CartController::class, 'store'])
    ->middleware('role:client')->name('panier.ajouter');

Route::post('/panier/quantite/{slug}', [CartController::class, 'update'])
    ->middleware('role:client')->name('panier.quantite');

Route::post('/panier/supprimer/{slug}', [CartController::class, 'destroy'])
    ->middleware('role:client')->name('panier.supprimer');

Route::post('/panier/vider', [CartController::class, 'clear'])
    ->middleware('role:client')->name('panier.vider');

Route::get('/panier', [CartController::class, 'index'])
    ->middleware('role:client')->name('panier');

// Checkout & Mobile Money Payment
Route::get('/checkout', function () {
    $user = Auth::user();
    $items = app(CartService::class)->toViewItems($user);
    abort_if($items === [], 400, 'Votre panier doit contenir au moins un produit valide.');
    $total = app(CartService::class)->subtotal($user);
    return view('checkout', ['items' => $items, 'total' => $total, 'user' => $user]);
})->middleware('role:client')->name('checkout');

Route::post('/checkout', function (\App\Http\Requests\CheckoutRequest $request) use ($cartService) {
    $validated = $request->validated();

    $orderService = app(OrderService::class);

    // Persistent MySQL order: prices snapshotted from the products table.
    $order = $orderService->placeOrder(Auth::user(), $validated);

    // The legacy session order arrays are gone: drain them once the MySQL
    // equivalent exists (confirmed by the feature tests).
    foreach (OrderService::LEGACY_SESSION_KEYS as $legacyKey) {
        session()->forget($legacyKey);
    }
    session()->forget('cart');
    // Mission 12: the persistent order now goes through the simulated payment page.
    return redirect()->route('payment.show', ['reference' => $order->reference])
        ->with('success', 'Votre commande ' . $order->reference . ' a été enregistrée, finalisez le paiement.');
})->middleware('role:client')->name('checkout.submit');

Route::get('/mes-commandes', function () {
    return redirect()->route('client.dashboard', ['tab' => 'orders']);
})->middleware('role:client')->name('mes.commandes');

// Mission 14 — persistent MySQL messaging (Client ↔ Producer), isolation enforced in the controller.
Route::get('/messagerie', [MessagingController::class, 'index'])
    ->middleware('role:client,producer')->name('messagerie');
Route::get('/discussion/{id}', [MessagingController::class, 'show'])
    ->middleware('role:client,producer')->name('discussion');
Route::post('/discussion/{id}', [MessagingController::class, 'send'])
    ->middleware('role:client,producer')->name('discussion.send');

// Géolocalisation — le navigateur appelle ces routes, jamais Geoapify
// directement : la clé API reste sur le serveur (GEOAPIFY_API_KEY dans .env).
//
// Pas de middleware `auth` : l'étape 3 de l'inscription est publique, le GPS
// doit donc y fonctionner. La clé n'est jamais renvoyée au client et `throttle`
// borne le quota gratuit Geoapify (30 reverse + 20 recherches par minute).
Route::post('/geolocation/reverse', [GeoController::class, 'reverse'])
    ->middleware('throttle:30,1')
    ->name('geolocation.reverse');

Route::get('/geolocation/search', [GeoController::class, 'search'])
    ->middleware('throttle:20,1')
    ->name('geolocation.search');

// Bloc C (2/2) — Marché des prix (public, agrégation SQL locale).
Route::get('/marche-prix', [MarketIntelligenceController::class, 'prices'])->name('marche.prix');

// Bloc C (2/2) — Recommandations IA (Gemini), clients uniquement, appel async de la bulle.
Route::get('/recommandations-ia', [MarketIntelligenceController::class, 'recommendations'])
    ->middleware(['auth', 'role:client'])
    ->name('recommandations.ia');

// Pages institutionnelles : confidentialité & à-propos.
Route::view('/confidentialite', 'pages.confidentialite')->name('confidentialite');
Route::view('/a-propos', 'pages.a-propos')->name('a-propos');

// Public Producer Profile — real database lookup (was hardcoded demo data).
Route::get('/profil/{id}', function (string $id) {
    $producteur = \App\Models\User::where('id', $id)->where('role', 'producer')->firstOrFail();

    $reviews = app(\App\Services\ProducerReviewService::class);

    // Commande payée du visiteur courant contenant ce producteur, sur laquelle
    // il n'a pas encore noté : c'est la seule commande qui autorise la notation.
    $notationOrder = null;
    if (auth()->check() && auth()->user()->role === 'client' && auth()->id() !== (int) $id) {
        $notationOrder = \App\Models\Order::query()
            ->where('client_id', auth()->id())
            ->where(function ($q) {
                $q->where('status', 'delivered')
                    ->orWhereHas('payments', fn ($p) => $p->where('status', 'paid'));
            })
            ->whereHas('items', fn ($q) => $q->where('producer_id', $producteur->id))
            ->orderByDesc('id')
            ->get()
            ->first(fn ($order) => ! \App\Models\ProducerReview::query()
                ->where('order_id', $order->id)
                ->where('client_id', auth()->id())
                ->where('producer_id', $producteur->id)
                ->exists());
    }

    return view('profil', [
        'producteur' => $producteur,
        'isCurrentUser' => auth()->check() && auth()->id() === (int) $id,
        // Statistiques calculees depuis MySQL (moyenne, nombre, repartition).
        'score' => $reviews->producerScore($producteur),
        'stats' => $reviews->producerStats($producteur),
        'avis' => $reviews->latestForProducer($producteur),
        'notationOrder' => $notationOrder,
    ]);
})->name('profil');

Route::middleware('auth')->get('/mon-profil', function () {
    $user = Auth::user();
    if ($user->role === 'producer') {
        return redirect()->route('producer.dashboard', ['tab' => 'account']);
    }
    return redirect()->route('client.dashboard', ['tab' => 'account']);
})->name('mon.profil');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Mission 12 — simulated payment, client only (IDOR-safe inside the controller).
Route::get('/paiement/{reference}', [PaymentController::class, 'show'])
    ->middleware(['auth', 'role:client'])->name('payment.show');
Route::get('/paiement/{reference}/{outcome}', [PaymentController::class, 'decide'])
    ->middleware(['auth', 'role:client'])->name('payment.decide');

// Bloc D — Assistant repas conversationnel (clients uniquement).
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/assistant-repas', [DishAssistantController::class, 'index'])->name('assistant-repas');
    Route::post('/assistant-repas', [DishAssistantController::class, 'send'])->name('assistant-repas.send');
    Route::post('/assistant-repas/reset', [DishAssistantController::class, 'reset'])->name('assistant-repas.reset');
    Route::get('/assistant-repas/historique', [DishAssistantController::class, 'history'])->name('assistant-repas.history');
});

// Client Dashboard & Portal Actions
Route::middleware(['auth', 'role:client'])->group(function () {
    Route::get('/client/dashboard', [DashboardController::class, 'clientDashboard'])->name('client.dashboard');
    Route::post('/client/notifications/read', [DashboardController::class, 'markClientNotificationsRead'])->name('client.notifications.read');
    Route::post('/client/account', [DashboardController::class, 'updateClientAccount'])->name('client.account.update');

    // Notation d'un producteur après une transaction (commande payée ou livrée).
    Route::post('/client/orders/{order}/producer/{producer}/avis', [ProducerReviewController::class, 'store'])
        ->name('client.producer-review.store');
    // Modification / suppression : strictement son auteur (ProducerReviewPolicy).
    Route::put('/client/producer-reviews/{review}', [ProducerReviewController::class, 'update'])
        ->name('client.producer-review.update');
    Route::delete('/client/producer-reviews/{review}', [ProducerReviewController::class, 'destroy'])
        ->name('client.producer-review.destroy');
});

// Producer Dashboard & Portal Actions
Route::middleware(['auth', 'role:producer'])->group(function () {
    Route::get('/producer/dashboard', [DashboardController::class, 'producerDashboard'])->name('producer.dashboard');
    Route::post('/producer/products', [DashboardController::class, 'addProduct'])->name('producer.products.add');
    Route::post('/producer/products/{slug}/toggle', [DashboardController::class, 'toggleProductStatus'])->name('producer.products.toggle');
    Route::post('/producer/products/{slug}/update', [DashboardController::class, 'updateProduct'])->name('producer.products.update');
    Route::delete('/producer/products/{slug}', [DashboardController::class, 'deleteProduct'])->name('producer.products.delete');
    Route::post('/producer/orders/{orderId}/status', [DashboardController::class, 'updateOrderStatus'])->name('producer.orders.status');
    Route::post('/producer/withdraw', [DashboardController::class, 'withdraw'])->name('producer.withdraw');
    Route::post('/producer/notifications/read', [DashboardController::class, 'markProducerNotificationsRead'])->name('producer.notifications.read');
    Route::post('/producer/account', [DashboardController::class, 'updateProducerAccount'])->name('producer.account.update');
});

// Admin Dashboard & Backend (Bloc B — role:admin, re-checked in the controller)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/{user}', [AdminController::class, 'userShow'])->name('admin.users.show');
    Route::post('/admin/users/{user}/suspend', [AdminController::class, 'userSuspend'])->name('admin.users.suspend');
    Route::post('/admin/users/{user}/reactivate', [AdminController::class, 'userReactivate'])->name('admin.users.reactivate');
    Route::get('/admin/verifications', [AdminController::class, 'verifications'])->name('admin.verifications');
    Route::post('/admin/verifications/{verification}', [AdminController::class, 'verificationDecide'])->name('admin.verifications.decide');
    Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::post('/admin/reports/{report}', [AdminController::class, 'reportDecide'])->name('admin.reports.decide');
    Route::delete('/admin/products/{product}', [AdminController::class, 'productDelete'])->name('admin.products.delete');
    Route::get('/admin/products', [AdminController::class, 'products'])->name('admin.products');
    Route::post('/admin/products/{product}/status', [AdminController::class, 'productStatus'])->name('admin.products.status');
    Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders');
    Route::get('/admin/reviews', [AdminController::class, 'reviews'])->name('admin.reviews');
    Route::post('/admin/reviews/{review}/hide', [AdminController::class, 'reviewHide'])->name('admin.reviews.hide');
    Route::post('/admin/reviews/{review}/restore', [AdminController::class, 'reviewRestore'])->name('admin.reviews.restore');
    Route::get('/admin/prices', [AdminController::class, 'prices'])->name('admin.prices');

    // Bloc D — Base de connaissance des plats locaux (CRUD admin).
    Route::get('/admin/dishes', [AdminController::class, 'dishes'])->name('admin.dishes');
    Route::post('/admin/dishes', [AdminController::class, 'dishStore'])->name('admin.dishes.store');
    Route::put('/admin/dishes/{dish}', [AdminController::class, 'dishUpdate'])->name('admin.dishes.update');
    Route::delete('/admin/dishes/{dish}', [AdminController::class, 'dishDestroy'])->name('admin.dishes.destroy');
    Route::post('/admin/dishes/extract', [AdminController::class, 'dishExtract'])->name('admin.dishes.extract');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/admin/settings/categories', [AdminController::class, 'categoryStore'])->name('admin.settings.categories.store');
    Route::delete('/admin/settings/categories/{category}', [AdminController::class, 'categoryDelete'])->name('admin.settings.categories.delete');
});