<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc C — Geolocation: approximate coordinates on the producer profile,
 * filled by the Google Geocoding API (nullable until geocoding succeeds).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producer_profiles', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('description');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->timestamp('geocoded_at')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('producer_profiles', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'geocoded_at']);
        });
    }
};
