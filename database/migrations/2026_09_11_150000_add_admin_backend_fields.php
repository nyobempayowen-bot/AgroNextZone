<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc B — additive-only schema for the admin backend.
 * No existing column or row is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Account suspension (soft lock, never a physical delete).
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_verified');
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->index('status');
        });

        // Product reports (signalements) for moderation.
        if (! Schema::hasTable('product_reports')) {
            Schema::create('product_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason', 50); // counterfeit, quality, pricing, scam, other
                $table->text('comment')->nullable();
                $table->string('status', 20)->default('pending'); // pending | approved | dismissed
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reports');
        Schema::table('users', function (Blueprint $table) {
            foreach (['status', 'suspended_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
