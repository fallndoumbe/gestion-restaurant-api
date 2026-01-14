<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
   public function run(): void
    {
        $items = [
            [
                'name' => 'Thieboudienne',
                'description' => 'Plat national sénégalais à base de riz et poisson',
                'price' => 3500,
                'category_id' => 1,
                'preparation_time' => 25,
                'is_available' => true
            ],
            [
                'name' => 'Yassa Poulet',
                'description' => 'Poulet mariné aux oignons et citron',
                'price' => 3000,
                'category_id' => 1,
                'preparation_time' => 20,
                'is_available' => true
            ],
            [
                'name' => 'Bissap',
                'description' => 'Jus d\'hibiscus frais',
                'price' => 500,
                'category_id' => 4,
                'preparation_time' => 5,
                'is_available' => true
            ],
        ];

        foreach ($items as $item) {
            MenuItem::create($item);
        }
    }
}
