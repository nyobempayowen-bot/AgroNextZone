<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mission 16 — persistent producer transactions from REAL sales.
 *
 * - The amount is the sum of the producer's own order items
 *   (line_total), taken from MySQL — never invented, no fake commission.
 * - One `sale` transaction per order per producer (unique reference
 *   TRX-SALE-<order_id>-<producer_id> prevents duplicates).
 * - Only the producer's own sales are ever readable.
 */
class TransactionService
{
    public function recordSaleForOrder(Order $order, int $producerId, ?Payment $payment = null): Transaction
    {
        return DB::transaction(function () use ($order, $producerId, $payment) {
            $reference = 'TRX-SALE-' . $order->id . '-' . $producerId;

            // Idempotent: an existing sale row for this order+producer wins.
            $existing = Transaction::query()->where('reference', $reference)->first();
            if ($existing) {
                return $existing;
            }

            $amount = (float) $order->items()
                ->where('producer_id', $producerId)
                ->sum('line_total');

            if ($amount <= 0) {
                // Nothing sold by this producer in this order — no row at all.
                abort(403, 'Aucun de vos produits dans cette commande.');
            }

            return Transaction::create([
                'producer_id' => $producerId,
                'order_id' => $order->id,
                'payment_id' => $payment?->id,
                'type' => 'sale',
                'status' => ($payment && $payment->status === 'paid') || $order->status === 'delivered'
                    ? 'completed'
                    : 'pending',
                'amount' => round($amount, 2),
                'reference' => $reference,
                'occurred_at' => $order->placed_at ?? $order->created_at,
            ]);
        });
    }

    /** The producer's own transaction history (IDOR-safe). */
    public function forProducer(User $producer): array
    {
        return Transaction::query()
            ->where('producer_id', $producer->id)
            ->with('order')
            ->orderByDesc('occurred_at')
            ->get()
            ->map(fn(Transaction $t): array => [
                'id' => $t->reference ?? ('TRX-' . $t->id),
                'date' => $t->occurred_at->format('d/m/Y H:i'),
                'type' => $t->type,
                'montant_num' => (float) $t->amount,
                'montant' => ($t->type === 'withdrawal' ? '-' : '') . number_format((float) $t->amount, 0, ',', ' ') . ' FCFA',
                'statut' => match ($t->status) {
                    'completed' => 'Terminé',
                    'pending' => 'En cours',
                    'failed' => 'Échoué',
                    'cancelled' => 'Annulé',
                    default => $t->status,
                },
            ])
            ->all();
    }

    /** Revenue = completed sale rows only, straight from MySQL. */
    public function revenue(User $producer): float
    {
        return (float) Transaction::query()
            ->where('producer_id', $producer->id)
            ->where('type', 'sale')
            ->where('status', 'completed')
            ->sum('amount');
    }

    /** Withdrawal row (typed, never a fake sale). */
    public function recordWithdrawal(User $producer, float $amount, string $method): Transaction
    {
        return Transaction::create([
            'producer_id' => $producer->id,
            'order_id' => null,
            'payment_id' => null,
            'type' => 'withdrawal',
            'status' => 'pending',
            'amount' => round($amount, 2),
            'reference' => 'TRX-WDR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'occurred_at' => now(),
        ]);
    }
}
