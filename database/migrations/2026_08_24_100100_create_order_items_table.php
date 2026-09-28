<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            // Snapshot historique : ces valeurs ne changent pas si le produit évolue.
            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->string('unit', 20);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
            $table->index('producer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
