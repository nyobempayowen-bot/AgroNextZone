<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

/**
 * Bloc C — catch-up geocoding for producers who registered before the
 * feature existed (or whose geocoding previously failed). Safe to re-run.
 */
class GeocodeProducers extends Command
{
    protected $signature = 'geocode:producers {--force : Re-geocode even producers already geocoded}';

    protected $description = 'Geocode producer profiles that have no coordinates yet (Google Geocoding API)';

    public function handle(GeocodingService $geocoding): int
    {
        $query = User::query()->where('role', 'producer')->with('producerProfile');

        if (! $this->option('force')) {
            $query->whereHas('producerProfile', fn ($q) => $q->whereNull('latitude')->whereNull('longitude'));
        }

        $producers = $query->get();
        $success = 0;
        $failed = 0;

        foreach ($producers as $producer) {
            $before = [$producer->producerProfile?->latitude, $producer->producerProfile?->longitude];
            $geocoding->geocodeProducer($producer);
            $producer->refresh();

            [$lat, $lng] = [$producer->producerProfile?->latitude, $producer->producerProfile?->longitude];

            if ($lat !== null && $lng !== null && $before !== [$lat, $lng]) {
                $success++;
                $this->info("✓ {$producer->name} → {$lat}, {$lng}");
            } else {
                $failed++;
                $this->warn("✗ {$producer->name} — non géocodé (adresse introuvable, quota ou profil vide)");
            }
        }

        $this->line("Terminé : {$success} géocodé(s), {$failed} en échec.");

        return self::SUCCESS;
    }
}
