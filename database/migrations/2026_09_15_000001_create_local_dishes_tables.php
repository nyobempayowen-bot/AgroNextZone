<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc D — Base de connaissance des plats locaux.
 * Source de vérité unique sur la composition des plats : c'est l'admin
 * qui renseigne cette base manuellement (CRUD admin), jamais l'IA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('local_dishes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('region')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('local_dish_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_dish_id')->constrained('local_dishes')->cascadeOnDelete();
            $table->string('ingredient_name');
            $table->foreignId('product_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();

            $table->index(['local_dish_id']);
            $table->index(['ingredient_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_dish_ingredients');
        Schema::dropIfExists('local_dishes');
    }
};
