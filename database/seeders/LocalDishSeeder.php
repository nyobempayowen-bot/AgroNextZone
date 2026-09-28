<?php

namespace Database\Seeders;

use App\Models\LocalDish;
use App\Models\LocalDishIngredient;
use Illuminate\Database\Seeder;

/**
 * Bloc D — Seeder de départ : 6 plats camerounais courants avec leurs
 * ingrédients principaux (exemple que l'admin complétera via l'interface).
 */
class LocalDishSeeder extends Seeder
{
    public function run(): void
    {
        $dishes = [
            'Eru' => [
                'region' => 'Sud-Ouest / Nord-Ouest',
                'description' => 'Plat à base de feuilles d\'eru finement ciselées, servi avec le water-fufu.',
                'ingredients' => [
                    ['name' => 'Feuilles d\'eru', 'optional' => false],
                    ['name' => 'Water-fufu (cassave)', 'optional' => false],
                    ['name' => 'Huile de palme', 'optional' => false],
                    ['name' => 'Crevettes fumées', 'optional' => false],
                    ['name' => 'Viande fumée (kondrè)', 'optional' => true],
                    ['name' => 'Piment', 'optional' => true],
                ],
            ],
            'Ndolé' => [
                'region' => 'Littoral (Douala)',
                'description' => 'Plat national à base de feuilles de ndolé (vernonia) et de pâte d\'arachide.',
                'ingredients' => [
                    ['name' => 'Feuilles de ndolé', 'optional' => false],
                    ['name' => 'Pâte d\'arachide', 'optional' => false],
                    ['name' => 'Huile de palme', 'optional' => false],
                    ['name' => 'Crevettes', 'optional' => false],
                    ['name' => 'Viande de bœuf', 'optional' => true],
                    ['name' => 'Poisson fumé', 'optional' => true],
                ],
            ],
            'Koki' => [
                'region' => 'Centre / Ouest',
                'description' => 'Papillote de haricots coco moulus à la vapeur, dans des feuilles de bananier.',
                'ingredients' => [
                    ['name' => 'Haricots coco', 'optional' => false],
                    ['name' => 'Huile de palme', 'optional' => false],
                    ['name' => 'Feuilles de bananier', 'optional' => false],
                    ['name' => 'Piment', 'optional' => true],
                ],
            ],
            'Bâton de manioc' => [
                'region' => 'Sud / Est',
                'description' => 'Manioc fermenté roulé et cuit en papillote dans des feuilles de bananier.',
                'ingredients' => [
                    ['name' => 'Manioc', 'optional' => false],
                    ['name' => 'Feuilles de bananier', 'optional' => false],
                ],
            ],
            'Okok' => [
                'region' => 'Sud (Beti)',
                'description' => 'Plat à base de feuilles de gnetum (ok) pilées, à l\'huile de palme.',
                'ingredients' => [
                    ['name' => 'Feuilles d\'ok (gnetum)', 'optional' => false],
                    ['name' => 'Huile de palme', 'optional' => false],
                    ['name' => 'Arachides grillées', 'optional' => false],
                    ['name' => 'Viande fumée', 'optional' => true],
                ],
            ],
            'Poulet DG' => [
                'region' => 'Ouest / National',
                'description' => 'Plat festif : poulet sauté avec des plantains mûrs et des légumes.',
                'ingredients' => [
                    ['name' => 'Poulet', 'optional' => false],
                    ['name' => 'Plantain', 'optional' => false],
                    ['name' => 'Huile (arachide)', 'optional' => false],
                    ['name' => 'Carottes', 'optional' => true],
                    ['name' => 'Haricots verts', 'optional' => true],
                ],
            ],
        ];

        foreach ($dishes as $name => $data) {
            $dish = LocalDish::firstOrCreate(
                ['name' => $name],
                ['region' => $data['region'], 'description' => $data['description']]
            );

            foreach ($data['ingredients'] as $ing) {
                LocalDishIngredient::firstOrCreate([
                    'local_dish_id' => $dish->id,
                    'ingredient_name' => $ing['name'],
                    'is_optional' => $ing['optional'],
                ]);
            }
        }
    }
}
