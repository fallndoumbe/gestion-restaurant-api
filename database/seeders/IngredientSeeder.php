<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            ['name' => 'Riz', 'unit' => 'kg', 'stock_quantity' => 100, 'min_stock' => 20, 'cost_per_unit' => 500],
            ['name' => 'Poulet', 'unit' => 'kg', 'stock_quantity' => 50, 'min_stock' => 10, 'cost_per_unit' => 2500],
            ['name' => 'Huile', 'unit' => 'litre', 'stock_quantity' => 25, 'min_stock' => 5, 'cost_per_unit' => 1200],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::create($ingredient);
        }
    }
}
