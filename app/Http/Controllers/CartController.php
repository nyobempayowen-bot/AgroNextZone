<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Persistent MySQL cart controller. Reserved to authenticated clients.
 */
class CartController extends Controller
{
    public function __construct(private readonly CartService $cart)
    {
    }

    /**
     * Current cart page.
     */
    public function index(Request $request): View
    {
        // Sécurité : refuse tout utilisateur qui n'a pas le rôle 'client'.
        $user = $this->cart->assertClient(Auth::user());

        // Importe l'ancien panier en session (s'il existe) vers MySQL.
        $this->migrateLegacySessionCart($request, $user);

        // Recharge les lignes du panier et le total depuis la base MySQL.
        $items = $this->cart->toViewItems($user);
        $total = $this->cart->subtotal($user);

        return view('panier', ['items' => $items, 'total' => $total]);
    }

    /**
     * Add a product, or increment it when already in the cart.
     */
    public function store(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        // Seuls les clients peuvent ajouter au panier (403 sinon).
        $user = $this->cart->assertClient(Auth::user());

        // Migration éventuelle de l'ancien panier de session.
        $this->migrateLegacySessionCart($request, $user);

        // Validation de la quantité optionnelle (1 à 999, entier).
        $validated = $request->validate([
            'quantite' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        // Ajoute le produit (ou incrémente sa quantité s'il y est déjà).
        $this->cart->add($user, $slug, (int) ($validated['quantite'] ?? 1));

        // Réponse AJAX : renvoie le nouveau compteur et total du panier.
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'count' => $this->cart->count($user),
                'total' => $this->cart->subtotal($user),
                'message' => 'Produit ajouté au panier !',
            ]);
        }

        // Réponse classique : redirection vers la page du panier.
        return redirect()->route('panier')->with('success', 'Produit ajouté à votre panier.');
    }

    /**
     * Update the quantity of a product already in the cart.
     */
    public function update(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        // Accès réservé aux clients.
        $user = $this->cart->assertClient(Auth::user());

        // La nouvelle quantité est obligatoire et bornée (1 à 999).
        $validated = $request->validate([
            'quantite' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        // Met à jour la quantité de la ligne correspondante en base.
        $item = $this->cart->setQuantity($user, $slug, (int) $validated['quantite']);

        // Le produit absent du panier déclenche une erreur de validation.
        if (! $item) {
            throw ValidationException::withMessages([
                'quantite' => 'Ce produit ne figure pas dans votre panier.',
            ]);
        }

        // Réponse AJAX avec le nouvel état du panier.
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'count' => $this->cart->count($user),
                'total' => $this->cart->subtotal($user),
                'quantity' => (int) $item->quantity,
            ]);
        }

        return redirect()->route('panier')->with('success', 'Quantité mise à jour.');
    }

    /**
     * Remove one line from the cart.
     */
    public function destroy(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        // Accès réservé aux clients.
        $user = $this->cart->assertClient(Auth::user());

        // Retire la ligne du panier en base.
        $removed = $this->cart->remove($user, $slug);

        // Réponse AJAX : indique si la suppression a eu lieu.
        if ($request->wantsJson()) {
            return response()->json([
                'status' => $removed ? 'ok' : 'absent',
                'count' => $this->cart->count($user),
                'total' => $this->cart->subtotal($user),
                'message' => $removed ? 'Produit retiré du panier.' : 'Produit déjà absent du panier.',
            ]);
        }

        // Réponse classique : message adapté au résultat.
        return redirect()->route('panier')->with(
            $removed ? 'success' : 'info',
            $removed ? 'Produit retiré du panier.' : 'Ce produit n\'était plus dans votre panier.'
        );
    }

    /**
     * Empty the cart.
     */
    public function clear(Request $request): RedirectResponse|JsonResponse
    {
        // Accès réservé aux clients.
        $user = $this->cart->assertClient(Auth::user());

        // Supprime toutes les lignes du panier en base.
        $deleted = $this->cart->clear($user);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'count' => 0, 'total' => 0.0, 'deleted' => $deleted]);
        }

        return redirect()->route('panier')->with('success', 'Votre panier a été vidé.');
    }

    /**
     * Progressive migration away from the legacy session cart.
     * The session cart is only imported when it still carries valid products,
     * then removed so the MySQL cart becomes the single source of truth.
     */
    private function migrateLegacySessionCart(Request $request, User $user): void
    {
        // Lit l'ancien panier stocké en session (format hérité).
        $legacy = $request->session()->get(CartService::LEGACY_SESSION_KEY);

        // Rien à migrer : on sort immédiatement.
        if (! is_array($legacy) || $legacy === []) {
            return;
        }

        // Importe les produits valides vers le panier MySQL...
        $this->cart->importLegacySessionCart($user, $legacy);
        // ...puis supprime la clé de session : MySQL devient l'unique source de vérité.
        $request->session()->forget(CartService::LEGACY_SESSION_KEY);
    }
}
