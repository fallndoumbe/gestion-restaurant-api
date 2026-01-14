<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
   public function run(): void
    {
        $categories = [
            ['name' => 'Plats Principaux', 'description' => 'Plats traditionnels sénégalais', 'display_order' => 1],
            ['name' => 'Entrées', 'description' => 'Entrées variées', 'display_order' => 2],
            ['name' => 'Desserts', 'description' => 'Desserts maison', 'display_order' => 3],
            ['name' => 'Boissons', 'description' => 'Boissons fraîches', 'display_order' => 4],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
