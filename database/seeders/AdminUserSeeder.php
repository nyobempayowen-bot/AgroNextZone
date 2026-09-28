<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Compte administrateur permanent d'AgroNextZone.
 *
 * Idempotent : met à jour le mot de passe/rôle si le compte existe déjà,
 * le crée sinon. Enregistré dans DatabaseSeeder → l'admin survit à toute
 * réinitialisation de la base (php artisan migrate:fresh --seed).
 *
 * ⚠️ Changez ce mot de passe en production (ADMIN_PASSWORD dans .env).
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@agronextzone.com';
        $password = env('ADMIN_PASSWORD', 'AgroAdmin2026!');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrateur AgroNextZone',
                'role' => 'admin',
                'is_verified' => true,
                'password' => Hash::make($password),
            ]
        );
    }
}
