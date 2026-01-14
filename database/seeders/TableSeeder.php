<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Table;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['number' => 1, 'capacity' => 2, 'location' => 'intérieur'],
            ['number' => 2, 'capacity' => 4, 'location' => 'intérieur'],
            ['number' => 3, 'capacity' => 4, 'location' => 'terrasse'],
            ['number' => 4, 'capacity' => 6, 'location' => 'terrasse'],
            ['number' => 5, 'capacity' => 8, 'location' => 'salon privé'],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
