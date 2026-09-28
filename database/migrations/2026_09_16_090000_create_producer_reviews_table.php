<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notation directe d'un producteur par un client, après une transaction
 * réelle (commande payée ou livrée). Un avis unique par commande et
 * par client, ce qui permet d'évaluer le producteur à chaque nouvelle
 * transaction (contrairement à l'avis produit, unique par produit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1 à 5
            $table->text('comment')->nullable();
            $table->string('status', 20)->default('published'); // pending, published, hidden, rejected
            $table->timestamps();

            // Un seul avis producteur par commande : la transaction fait foi.
            $table->unique(['order_id', 'client_id']);
            $table->index(['producer_id', 'status']);
            $table->index('client_id');
        });

        // Contrainte SQL : rating entre 1 et 5 (cohérent avec la table reviews).
        DB::statement('ALTER TABLE producer_reviews ADD CONSTRAINT chk_producer_reviews_rating CHECK (rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_reviews');
    }
};
