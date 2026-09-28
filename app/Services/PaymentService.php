<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TransactionService;
use Illuminate\Support\Str;

/**
 * Mission 12 — persistent simulated Mobile Money payments.
 *
 * Architecture: Checkout -> PaymentService -> Payment -> MySQL.
 * A future real provider (MTN / Orange sandbox) will plug in behind
 * `providerConfigured()`: when `PAYMENT_SIMULATION` stays true (the default,
 * no credentials exist), nothing pretends a real transfer happened.
 */
class PaymentService
{
    /** Payment methods mapped from the checkout `moyen_paiement` labels. */
    public const METHODS = [
        'MTN Mobile Money' => 'mtn_mobile_money',
        'Orange Money' => 'orange_money',
        'Paiement à la livraison' => 'cash_on_delivery',
        'Virement bancaire' => 'bank_transfer',
    ];

    /** The existing Payment model uses 'paid' as its success status. */
    public const STATUSES = ['pending', 'paid', 'failed', 'cancelled'];

    /**
     * Create (or resume) the payment of an order. The amount ALWAYS comes
     * from the MySQL order row — never from any browser payload.
     */
    public function initiate(Order $order): Payment
    {
        // No duplicate: an existing successful payment (or one already in
        // progress) is reused instead of creating a second payment row.
        $existing = Payment::query()->where('order_id', $order->id)->latest('id')->first();

        if ($existing && in_array($existing->status, ['paid', 'pending'], true)) {
            return $existing;
        }

        return Payment::create([
            'order_id' => $order->id,
            'client_id' => $order->client_id,
            'method' => $order->payment_method,
            'status' => 'pending',
            'amount' => (float) $order->total,
            'provider' => $this->providerName($order->payment_method),
            'provider_reference' => $this->generateReference(),
        ]);
    }

    /** Server-side unique payment reference (e.g. PAY-2026-XXXXXXXX). */
    public function generateReference(): string
    {
        do {
            $reference = 'PAY-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
        } while (Payment::query()->where('provider_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Simulated provider decision. When a real provider is configured later
     * (config/services.php + .env keys), this method is the single seam to
     * replace with an API call + webhook handling.
     */
    public function process(Payment $payment, string $outcome = 'successful'): Payment
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payment, $outcome) {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->status !== 'pending') {
                return $payment; // already resolved — idempotent, no double effects
            }

            match ($outcome) {
                'successful' => $payment->update(['status' => 'paid', 'paid_at' => now()]),
                'failed' => $payment->update(['status' => 'failed', 'failure_reason' => 'Simulation : paiement refusé par le fournisseur.']),
                'cancelled' => $payment->update(['status' => 'cancelled', 'failure_reason' => 'Simulation : paiement annulé par le client.']),
                default => throw new \InvalidArgumentException("Unknown payment outcome [{$outcome}]."),
            };

            // Keep the order state consistent with the payment result.
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->first();
            if ($outcome === 'successful' && $order && $order->status === 'preparing') {
                $order->update(['status' => 'confirmed']);
            } elseif ($outcome !== 'successful' && $order) {
                $order->update(['status' => 'pending']);
            }

            $payment->refresh();

            // Missions 15 & 16 — business events fired exactly once, from MySQL state.
            $notifications = app(NotificationService::class);
            $transactions = app(TransactionService::class);

            if ($outcome === 'successful') {
                $client = $order?->client;
                if ($client) {
                    $notifications->send($client, 'payment', 'Paiement confirmé', "Votre paiement pour la commande {$order->reference} a été accepté.");
                }
                foreach ($order?->items()->pluck('producer_id')->unique() ?? collect() as $producerId) {
                    $producer = \App\Models\User::find($producerId);
                    if ($producer) {
                        $notifications->send($producer, 'payment', 'Vente payée', "Le paiement de la commande {$order->reference} contenant vos produits a été confirmé.");
                        $transactions->recordSaleForOrder($order, (int) $producerId, $payment);
                    }
                }
            } elseif ($order?->client) {
                $notifications->send($order->client, 'payment', 'Paiement non abouti', "Le paiement de la commande {$order->reference} a été {$outcome}.");
            }

            return $payment;
        });
    }

    /** IDOR gate: only the owning client may act on a payment. */
    public function assertClientOwns($user, Payment $payment): void
    {
        if (! $user || (int) $user->id !== (int) $payment->client_id) {
            abort(403, 'Vous ne pouvez pas accéder à ce paiement.');
        }
    }

    public function providerName(string $method): string
    {
        return str_contains($method, 'MTN') ? 'mtn_momo_simulated'
            : (str_contains($method, 'Orange') ? 'orange_money_simulated' : 'manual_simulated');
    }

    /** True only when a real provider is configured — never true today. */
    public function providerConfigured(): bool
    {
        return config('services.mobile_money.simulation') === false
            && filled(config('services.mobile_money.api_key'));
    }
}
