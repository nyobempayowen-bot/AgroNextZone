<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReport;
use App\Models\ProducerVerification;
use App\Models\Review;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Bloc B — admin backend, all rules enforced server-side.
 *
 * - Suspension is a soft lock (users.status), never a physical delete;
 *   a user with linked orders/transactions can never be hard-deleted.
 * - Verification approve/reject writes ProducerVerification + flips
 *   is_verified, and notifies the producer via the Mission 15 system.
 * - Report moderation never invents state: approve = product suspended
 *   (status 'suspended'), dismiss = report closed, product untouched.
 */
class AdminService
{
    // ---------- USERS ----------

    public function users(string $role = 'all', string $search = ''): LengthAwarePaginator
    {
        return User::query()
            ->when(in_array($role, ['client', 'producer', 'admin'], true), fn ($q) => $q->where('role', $role))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->withCount(['orders', 'producerTransactions'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function userDetail(User $user): User
    {
        return $user->loadCount(['orders', 'producerTransactions', 'products'])
            ->load(['producerProfile', 'producerVerification', 'locations']);
    }

    /** Users with a pending ProducerVerification (comptes à valider). */
    public function pendingProducers(): array
    {
        return ProducerVerification::query()
            ->where('status', ProducerVerification::STATUS_PENDING)
            ->with('producer')
            ->orderBy('submitted_at')
            ->get()
            ->all();
    }

    public function suspendUser(User $user, string $reason = ''): User
    {
        if ($user->role === 'admin') {
            abort(403, 'Un administrateur ne peut pas être suspendu.');
        }

        $user->forceFill([
            'status' => 'suspended',
            'suspended_at' => now(),
        ])->save();

        app(NotificationService::class)->send(
            $user,
            'account',
            'Compte suspendu',
            $reason !== ''
                ? "Votre compte a été suspendu. Motif : {$reason}"
                : 'Votre compte a été suspendu par l\'administration.'
        );

        return $user;
    }

    public function reactivateUser(User $user): User
    {
        $user->forceFill([
            'status' => 'active',
            'suspended_at' => null,
        ])->save();

        app(NotificationService::class)->send(
            $user,
            'account',
            'Compte réactivé',
            'Votre compte AgroNextZone est de nouveau actif.'
        );

        return $user;
    }

    /** Physical delete is FORBIDDEN when business rows are attached. */
    public function assertDeletable(User $user): void
    {
        $hasBusinessRows = $user->orders()->exists() || $user->producerTransactions()->exists();

        abort_if($hasBusinessRows, 403, 'Suppression impossible : ce compte a des commandes ou transactions liées.');
    }

    // ---------- VERIFICATION ----------

    public function approveVerification(ProducerVerification $verification): ProducerVerification
    {
        return DB::transaction(function () use ($verification) {
            if ($verification->status !== ProducerVerification::STATUS_PENDING) {
                return $verification; // idempotent
            }

            $verification->update([
                'status' => ProducerVerification::STATUS_APPROVED,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $verification->producer?->forceFill(['is_verified' => true])->save();

            app(NotificationService::class)->send(
                $verification->producer,
                'verification',
                'Compte validé',
                'Votre compte producteur a été validé par l\'administration.'
            );

            return $verification->refresh();
        });
    }

    public function rejectVerification(ProducerVerification $verification, string $reason = ''): ProducerVerification
    {
        return DB::transaction(function () use ($verification, $reason) {
            if ($verification->status !== ProducerVerification::STATUS_PENDING) {
                return $verification; // idempotent
            }

            $verification->update([
                'status' => ProducerVerification::STATUS_REJECTED,
                'reviewed_at' => now(),
                'rejection_reason' => $reason !== '' ? $reason : 'Dossier incomplet.',
            ]);

            $verification->producer?->forceFill(['is_verified' => false])->save();

            app(NotificationService::class)->send(
                $verification->producer,
                'verification',
                'Compte non validé',
                'Votre demande de validation a été rejetée. Motif : ' . ($reason !== '' ? $reason : 'Dossier incomplet.')
            );

            return $verification->refresh();
        });
    }

    // ---------- MODERATION ----------

    public function reportedProducts(): array
    {
        return ProductReport::query()
            ->where('status', 'pending')
            ->with(['product.producer', 'reporter'])
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    /** Approve = accept the report and suspend the offer (no stock/payment side effects). */
    public function approveReport(ProductReport $report): ProductReport
    {
        return DB::transaction(function () use ($report) {
            if ($report->status !== 'pending') {
                return $report;
            }

            $report->update(['status' => 'approved', 'resolved_at' => now()]);
            $report->product?->update(['status' => 'suspended', 'is_available' => false]);

            return $report->refresh();
        });
    }

    /** Dismiss = report closed, the offer stays untouched. */
    public function dismissReport(ProductReport $report): ProductReport
    {
        if ($report->status === 'pending') {
            $report->update(['status' => 'dismissed', 'resolved_at' => now()]);
        }

        return $report->refresh();
    }

    public function hardDeleteProduct(Product $product): void
    {
        // Business integrity: an offer already sold cannot be erased.
        abort_if($product->orderItems()->exists(), 403, 'Suppression impossible : cette offre a déjà été vendue.');

        $product->delete();
    }

    // ---------- STATS ----------

    /** All figures come from real MySQL aggregates — no mock. */
    public function stats(): array
    {
        return [
            'clients' => User::query()->where('role', 'client')->count(),
            'producers' => User::query()->where('role', 'producer')->count(),
            'pending_verifications' => ProducerVerification::query()->where('status', 'pending')->count(),
            'pending_reports' => ProductReport::query()->where('status', 'pending')->count(),
            'active_products' => Product::query()->where('status', 'published')->where('is_available', true)->count(),
            'orders' => Order::query()->count(),
            'orders_pending' => Order::query()->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'volume' => (float) Transaction::query()->where('type', 'sale')->where('status', 'completed')->sum('amount'),
            'transactions' => Transaction::query()->count(),
            'suspended_users' => User::query()->where('status', 'suspended')->count(),
            'recent_users' => User::query()->orderByDesc('id')->limit(5)->get(['id', 'name', 'role', 'status', 'created_at']),
            'recent_orders' => Order::query()->with('client')->orderByDesc('id')->limit(5)->get(),
        ];
    }

    // ---------- PRODUCTS (admin catalogue + moderation priority) ----------

    /**
     * All offers, reported products first, then suspended, then the rest.
     * Each row carries its pending report reason (if any) for the admin view.
     */
    public function products(string $search = ''): LengthAwarePaginator
    {
        return Product::query()
            ->with(['producer', 'category'])
            ->withCount(['reports as pending_reports_count' => fn ($q) => $q->where('status', 'pending')])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('producer', fn ($p) => $p->where('name', 'like', "%{$search}%"))))
            ->orderByDesc('pending_reports_count')
            ->orderByRaw("CASE status WHEN 'suspended' THEN 0 WHEN 'published' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /** Pending report reasons of one product, for the detail display. */
    public function productReports(Product $product): array
    {
        return ProductReport::query()
            ->where('product_id', $product->id)
            ->where('status', 'pending')
            ->with('reporter')
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    /** Direct admin moderation (outside any report): real effect in base. */
    public function setProductStatus(Product $product, string $status): Product
    {
        abort_unless(in_array($status, ['published', 'suspended'], true), 400, 'Statut d\'offre inconnu.');

        $product->update([
            'status' => $status,
            'is_available' => $status === 'published',
        ]);

        return $product;
    }

    // ---------- ORDERS (read-only supervision) ----------

    public function orders(string $status = 'all'): LengthAwarePaginator
    {
        return Order::query()
            ->with(['client', 'items'])
            ->when(in_array($status, Order::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    // ---------- REVIEWS (moderation) ----------

    /**
     * All client reviews, abusive candidates (low ratings) first.
     * Hiding one recomputes the producer score (ReviewService reads
     * only 'published' rows), so the effect is real.
     */
    public function reviews(string $filter = 'all'): LengthAwarePaginator
    {
        return Review::query()
            ->with(['product.producer', 'client'])
            ->when($filter === 'published', fn ($q) => $q->where('status', 'published'))
            ->when($filter === 'hidden', fn ($q) => $q->where('status', 'hidden'))
            ->orderByRaw("CASE status WHEN 'published' THEN 0 ELSE 1 END, rating ASC, id DESC")
            ->paginate(20)
            ->withQueryString();
    }

    public function hideReview(Review $review): Review
    {
        if ($review->status === 'published') {
            $review->update(['status' => 'hidden']);
        }

        return $review->refresh();
    }

    public function restoreReview(Review $review): Review
    {
        if ($review->status === 'hidden') {
            $review->update(['status' => 'published']);
        }

        return $review->refresh();
    }

    // ---------- PRICE MARKET (read-only aggregates) ----------

    /** Mean / min / max price per product name, from real offers only. */
    public function priceMarket(): array
    {
        return Product::query()
            ->selectRaw("name, unit, COUNT(*) as offers_count,
                ROUND(AVG(price), 0) as avg_price,
                ROUND(MIN(price), 0) as min_price,
                ROUND(MAX(price), 0) as max_price")
            ->where('status', 'published')
            ->groupBy('name', 'unit')
            ->orderByDesc('offers_count')
            ->limit(50)
            ->get()
            ->all();
    }

    // ---------- SETTINGS (categories only — nothing invented) ----------

    public function categories(): \Illuminate\Support\Collection
    {
        return \App\Models\Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function createCategory(string $name): \App\Models\Category
    {
        return \App\Models\Category::query()->firstOrCreate(
            ['slug' => \Illuminate\Support\Str::slug($name)],
            ['name' => $name],
        );
    }
}
