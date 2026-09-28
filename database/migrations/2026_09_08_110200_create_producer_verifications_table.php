<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('cni_number');
            $table->text('document_path');
            $table->text('document_original_name')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_verifications');
    }
};
