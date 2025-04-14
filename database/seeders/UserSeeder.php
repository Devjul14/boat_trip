<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '1234567890',
        ]);

        // Create manager user
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'phone' => '0987654321',
        ]);

        // Create 5 boatmen
        $boatmen = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Bob Johnson', 'email' => 'bob@example.com'],
            ['name' => 'Sarah Williams', 'email' => 'sarah@example.com'],
            ['name' => 'Mike Brown', 'email' => 'mike@example.com'],
        ];

        foreach ($boatmen as $boatman) {
            User::create([
                'name' => $boatman['name'],
                'email' => $boatman['email'],
                'password' => Hash::make('password'),
                'role' => 'boatman',
                'phone' => '555' . rand(1000000, 9999999),
            ]);
        }
    }
}
