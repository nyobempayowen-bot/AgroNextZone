<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\ProducerVerification;
use App\Models\Review;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Bloc B — admin backend. Every action is server-side guarded:
 * the route group carries `role:admin` AND each method re-checks the
 * authenticated user's role (defense in depth, IDOR-safe).
 */
class AdminController extends Controller
{
    public function __construct(private AdminService $admin) {}

    private function assertAdmin(): void
    {
        if ((string) (auth()->user()?->role ?? '') !== 'admin') {
            abort(403);
        }
    }

    /** Admin dashboard: general statistics from real MySQL aggregates. */
    public function dashboard(): View
    {
        $this->assertAdmin();

        return view('dashboard.admin', [
            'user' => auth()->user(),
            'stats' => $this->admin->stats(),
        ]);
    }

    // ---------- USERS ----------

    public function users(Request $request): View
    {
        $this->assertAdmin();

        return view('admin.users', [
            'users' => $this->admin->users(
                (string) $request->query('role', 'all'),
                trim((string) $request->query('recherche', ''))
            ),
            'selectedRole' => (string) $request->query('role', 'all'),
            'search' => trim((string) $request->query('recherche', '')),
        ]);
    }

    public function userShow(User $user): View
    {
        $this->assertAdmin();

        return view('admin.user_show', ['account' => $this->admin->userDetail($user)]);
    }

    public function userSuspend(Request $request, User $user): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate(['motif' => ['nullable', 'string', 'max:500']]);

        $this->admin->suspendUser($user, $validated['motif'] ?? '');

        return redirect()->route('admin.users.show', ['user' => $user->id])
            ->with('success', 'Compte suspendu.');
    }

    public function userReactivate(User $user): RedirectResponse
    {
        $this->assertAdmin();

        $this->admin->reactivateUser($user);

        return redirect()->route('admin.users.show', ['user' => $user->id])
            ->with('success', 'Compte réactivé.');
    }

    // ---------- VERIFICATION ----------

    public function verifications(): View
    {
        $this->assertAdmin();

        return view('admin.verifications', ['pending' => $this->admin->pendingProducers()]);
    }

    public function verificationDecide(Request $request, ProducerVerification $verification): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['decision'] === 'approve') {
            $this->admin->approveVerification($verification);
        } else {
            $this->admin->rejectVerification($verification, $validated['motif'] ?? '');
        }

        return redirect()->route('admin.verifications')->with('success', 'Décision enregistrée et producteur notifié.');
    }

    // ---------- MODERATION ----------

    public function reports(): View
    {
        $this->assertAdmin();

        return view('admin.reports', ['reports' => $this->admin->reportedProducts()]);
    }

    public function reportDecide(Request $request, ProductReport $report): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate(['decision' => ['required', 'in:approve,dismiss']]);

        if ($validated['decision'] === 'approve') {
            $this->admin->approveReport($report);
        } else {
            $this->admin->dismissReport($report);
        }

        return redirect()->route('admin.reports')->with('success', 'Signalement traité.');
    }

    public function productDelete(Product $product): RedirectResponse
    {
        $this->assertAdmin();

        $this->admin->hardDeleteProduct($product);

        return redirect()->route('admin.reports')->with('success', 'Offre supprimée.');
    }

    // ---------- PRODUCTS (catalogue admin) ----------

    public function products(Request $request): View
    {
        $this->assertAdmin();

        return view('admin.products', [
            'products' => $this->admin->products(trim((string) $request->query('recherche', ''))),
            'search' => trim((string) $request->query('recherche', '')),
        ]);
    }

    public function productStatus(Request $request, Product $product): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate(['statut' => ['required', 'in:published,suspended']]);

        $this->admin->setProductStatus($product, $validated['statut']);

        return redirect()->route('admin.products')
            ->with('success', $validated['statut'] === 'published'
                ? 'Offre approuvée : elle est de nouveau visible sur la Marketplace.'
                : 'Offre suspendue : elle n\'apparaît plus sur la Marketplace.');
    }

    // ---------- ORDERS (lecture seule) ----------

    public function orders(Request $request): View
    {
        $this->assertAdmin();

        $status = (string) $request->query('statut', 'all');

        return view('admin.orders', [
            'orders' => $this->admin->orders($status),
            'statuses' => \App\Models\Order::STATUSES,
            'selectedStatus' => $status,
        ]);
    }

    // ---------- REVIEWS (moderation) ----------

    public function reviews(Request $request): View
    {
        $this->assertAdmin();

        $filter = (string) $request->query('filtre', 'all');

        return view('admin.reviews', [
            'reviews' => $this->admin->reviews($filter),
            'selectedFilter' => $filter,
        ]);
    }

    public function reviewHide(Review $review): RedirectResponse
    {
        $this->assertAdmin();

        $this->admin->hideReview($review);

        return redirect()->route('admin.reviews')->with('success', 'Évaluation masquée : le score du producteur est recalculé.');
    }

    public function reviewRestore(Review $review): RedirectResponse
    {
        $this->assertAdmin();

        $this->admin->restoreReview($review);

        return redirect()->route('admin.reviews')->with('success', 'Évaluation restaurée.');
    }

    // ---------- PRICE MARKET (lecture seule) ----------

    public function prices(): View
    {
        $this->assertAdmin();

        return view('admin.prices', ['prices' => $this->admin->priceMarket()]);
    }

    // ---------- SETTINGS (catégories uniquement) ----------

    public function settings(): View
    {
        $this->assertAdmin();

        return view('admin.settings', ['categories' => $this->admin->categories()]);
    }

    public function categoryStore(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate(['nom' => ['required', 'string', 'max:100']]);

        $category = $this->admin->createCategory($validated['nom']);

        return redirect()->route('admin.settings')->with('success', $category->wasRecentlyCreated
            ? 'Catégorie créée.'
            : 'Cette catégorie existe déjà.');
    }

    public function categoryDelete(Category $category): RedirectResponse
    {
        $this->assertAdmin();

        // Only empty categories can be removed (products keep their FK otherwise).
        abort_if($category->products()->exists(), 403, 'Impossible : des offres utilisent encore cette catégorie.');

        $category->delete();

        return redirect()->route('admin.settings')->with('success', 'Catégorie supprimée.');
    }

    // ---------- Bloc D — Plats locaux (base de connaissance IA) ----------

    public function dishes(Request $request): View
    {
        $this->assertAdmin();

        $query = LocalDish::with('ingredients')->orderBy('name');

        $search = trim((string) $request->query('recherche', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhereHas('ingredients', fn ($qi) => $qi->where('ingredient_name', 'like', "%{$search}%"));
            });
        }

        return view('admin.dishes', [
            'dishes' => $query->get(),
            'search' => $search,
            'editDish' => $request->query('edit') ? LocalDish::with('ingredients')->find((int) $request->query('edit')) : null,
        ]);
    }

    public function dishStore(Request $request): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:local_dishes,name',
            'region' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
            'ingredients' => 'nullable|string|max:4000',
        ]);

        $dish = LocalDish::create([
            'name' => $validated['name'],
            'region' => $validated['region'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncIngredients($dish, (string) ($validated['ingredients'] ?? ''));

        return redirect()->route('admin.dishes')->with('success', 'Plat "'.$dish->name.'" ajouté.');
    }

    public function dishUpdate(Request $request, LocalDish $dish): RedirectResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:local_dishes,name,'.$dish->id,
            'region' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
            'ingredients' => 'nullable|string|max:4000',
        ]);

        $dish->update([
            'name' => $validated['name'],
            'region' => $validated['region'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Remplacement complet des ingrédients (édition simple).
        if (array_key_exists('ingredients', $validated)) {
            $dish->ingredients()->delete();
            $this->syncIngredients($dish, (string) $validated['ingredients']);
        }

        return redirect()->route('admin.dishes')->with('success', 'Plat mis à jour.');
    }

    public function dishDestroy(LocalDish $dish): RedirectResponse
    {
        $this->assertAdmin();

        $name = $dish->name;
        $dish->delete(); // ingredients supprimés en cascade (FK).

        return redirect()->route('admin.dishes')->with('success', 'Plat "'.$name.'" supprimé.');
    }

    /**
     * Bloc D-2B — Saisie assistée par IA : extrait une composition de plat
     * d'un texte libre via OpenRouter. AUCUNE écriture en base ici : le JSON
     * est renvoyé au formulaire admin qui doit être relu et validé par
     * l'admin (POST admin.dishes.store) avant tout enregistrement.
     */
    public function dishExtract(Request $request): JsonResponse
    {
        $this->assertAdmin();

        $validated = $request->validate([
            'texte' => 'required|string|min:10|max:2000',
        ]);

        $ai = app(\App\Services\OpenRouterService::class);
        if (! $ai->isConfigured()) {
            return response()->json(['message' => 'Service IA non configuré — utilisez la saisie manuelle.'], 503);
        }

        $systemInstruction = "Tu aides un administrateur à alimenter la base de connaissance des plats locaux camerounais.\n"
            ."À partir de sa description en langage libre, réponds UNIQUEMENT avec du JSON valide, sans texte autour, au format exact :\n"
            ."{\"name\":\"<nom du plat>\",\"region\":\"<région ou null>\",\"description\":\"<description courte ou null>\",\"ingredients\":[{\"name\":\"<ingrédient>\",\"optional\":false}]}\n"
            ."Ne réponds pas si aucun plat n'est identifiable : réponds alors {\"error\":\"aucun plat identifiable\"}.";

        try {
            // Clé API dans l'en-tête Authorization uniquement (OpenRouterService).
            $text = $ai->complete(
                $systemInstruction,
                'Description : '.$validated['texte'],
                temperature: 0.2,
                maxTokens: 1200,
            );

            $decoded = $ai->decodeJson($text);

            if (isset($decoded['error'])) {
                return response()->json(['message' => $decoded['error']], 422);
            }

            // Normalisation stricte : rien n'est persisté, tout repart vers le formulaire.
            return response()->json([
                'name' => (string) ($decoded['name'] ?? ''),
                'region' => $decoded['region'] ?? null,
                'description' => $decoded['description'] ?? null,
                'ingredients' => collect($decoded['ingredients'] ?? [])
                    ->filter(fn ($i) => is_array($i) && ! empty($i['name']))
                    ->map(fn ($i) => [
                        'name' => (string) $i['name'],
                        'optional' => (bool) ($i['optional'] ?? false),
                    ])->values()->all(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('DishExtract: appel IA (OpenRouter) échoué.', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Service IA temporairement indisponible — utilisez la saisie manuelle.'], 503);
        }
    }

    /** Ingrédients au format "Nom | optionnel" par ligne (ex: "Manioc | non"). */
    private function syncIngredients(LocalDish $dish, string $raw): void
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $optional = false;
            if (preg_match('/\|\s*optionnel$/i', $line)) {
                $optional = true;
                $line = trim(preg_replace('/\|\s*optionnel$/i', '', $line));
            }

            if ($line !== '') {
                LocalDishIngredient::create([
                    'local_dish_id' => $dish->id,
                    'ingredient_name' => $line,
                    'is_optional' => $optional,
                ]);
            }
        }
    }
}
