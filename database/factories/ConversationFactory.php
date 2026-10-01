<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pour les conversations Client ↔ Producteur.
 */
class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id'       => User::factory()->create(['role' => 'client'])->id,
            'producer_id'     => User::factory()->create(['role' => 'producer'])->id,
            'last_message_at' => now(),
        ];
    }
}
