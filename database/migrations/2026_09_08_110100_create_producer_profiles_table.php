<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('activity_type', 120)->nullable();
            $table->string('specialty', 200)->nullable();
            $table->string('main_products', 255)->nullable();
            $table->string('farm_name', 150)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_profiles');
    }
};
