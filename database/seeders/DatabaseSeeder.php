<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::factory()->create([
            'name' => 'Admin Demo',
            'email' => 'admin@demo.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'phone' => '081234567890',
        ]);

        // Create Staff User
        User::factory()->create([
            'name' => 'Staff Demo',
            'email' => 'staff@demo.test',
            'password' => bcrypt('password'),
            'role' => 'staff',
            'phone' => '081234567891',
        ]);

        // Seed Geofence
        $this->call(GeofenceSeeder::class);
    }
}
