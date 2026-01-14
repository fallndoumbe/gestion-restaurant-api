<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Gérant
        User::create([
            'name' => 'Amadou Ba',
            'email' => 'manager@restaurant.com',
            'password' => Hash::make('password123'),
            'role' => 'manager',
            'phone' => '+221771234567'
        ]);

        // Serveur
        User::create([
            'name' => 'Awa Diop',
            'email' => 'server@restaurant.com',
            'password' => Hash::make('password123'),
            'role' => 'server',
            'phone' => '+221781234567'
        ]);

        // Client
        User::create([
            'name' => 'Moussa Ndiaye',
            'email' => 'client@example.com',
            'password' => Hash::make('password123'),
            'role' => 'client',
            'phone' => '+221791234567'
        ]);
    }
}