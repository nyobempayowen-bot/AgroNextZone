<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\NotificationService;
use App\Services\TransactionService;
use Illuminate\Http\Request;

/**
 * Mission 12 — payment actions (simulated Mobile Money).
 *
 * Security: only the owning Client can view or act on a payment
 * (IDOR-safe). Producers are excluded by the role middleware.
 */
class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $payments,
        private NotificationService $notifications,
        private TransactionService $transactions,
    ) {
    }

    /** Checkout → Payment → MySQL: open the (simulated) payment page. */
    public function show(Request $request, string $reference)
    {
        // Retrouve la commande par sa référence unique (404 si introuvable).
        $order = Order::query()->where('reference', $reference)->firstOrFail();
        // IDOR : seule la commande du client connecté peut être ouverte.
        \Illuminate\Support\Facades\Gate::authorize('view', $order);

        // Crée (ou récupère) le paiement de la commande côté service.
        $payment = $this->payments->initiate($order);

        return view('payment', [
            'order' => $order,
            'payment' => $payment,
            // Indique à la vue si le paiement est simulé (pas de vrai provider configuré).
            'simulation' => ! $this->payments->providerConfigured(),
        ]);
    }

    /** Simulated provider decision — the seam a real provider will replace. */
    public function decide(Request $request, string $reference, string $outcome)
    {
        // Whitelist stricte : seul 'successful' / 'failed' / 'cancelled' est admis.
        abort_unless(in_array($outcome, ['successful', 'failed', 'cancelled'], true), 404);

        // Retrouve la commande visée.
        $order = Order::query()->where('reference', $reference)->firstOrFail();

        // IDOR : seul le client propriétaire peut décider du paiement.
        \Illuminate\Support\Facades\Gate::authorize('decidePayment', $order);

        // Dernier paiement enregistré pour cette commande.
        $payment = Payment::query()->where('order_id', $order->id)->latest('id')->firstOrFail();

        // Applique la décision au paiement (statut paid / failed / cancelled).
        $this->payments->process($payment, $outcome);

        // Mission 15 + 16 — business events on payment resolution.
        // Recharge le paiement pour lire son statut final.
        $payment->refresh();
        $client = $order->client;
        // Liste unique des producteurs concernés par la commande.
        $producers = $order->items()->pluck('producer_id')->unique();

        // Paiement accepté : notifier le client et chaque producteur, puis enregistrer la vente.
        if ($payment->status === 'paid') {
            $this->notifications->send($client, 'payment', 'Paiement confirmé', "Votre paiement pour la commande {$order->reference} a été accepté.");
            foreach ($producers as $producerId) {
                $producer = \App\Models\User::find($producerId);
                if ($producer) {
                    $this->notifications->send($producer, 'payment', 'Vente payée', "Le paiement de la commande {$order->reference} contenant vos produits a été confirmé.");
                    // Mission 16 — enregistre une vraie transaction de vente à partir des lignes persistées de la commande.
                    $this->transactions->recordSaleForOrder($order, (int) $producerId, $payment);
                }
            }
        } elseif ($outcome !== 'successful') {
            // Paiement refusé/annulé : informe seulement le client.
            $this->notifications->send($client, 'payment', 'Paiement non abouti', "Le paiement de la commande {$order->reference} a été {$outcome}.");
        }

        // Retour sur la page de paiement avec le message correspondant à l'issue.
        return redirect()
            ->route('payment.show', ['reference' => $order->reference])
            ->with('success', $outcome === 'successful'
                ? 'Paiement simulé accepté (aucun argent réel n\'a été transféré).'
                : 'Paiement simulé ' . ($outcome === 'failed' ? 'refusé' : 'annulé') . '.');
    }
}
