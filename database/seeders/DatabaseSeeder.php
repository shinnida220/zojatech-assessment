<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'email' => 'admin@zojatech.com',
            'password' => Hash::make('Password@123'),
            'name' => 'Admin Zojatech',
            'email_verified_at' => now(),
            'user_type' => 'admin',
        ]);

        for ($i=1; $i<= 10; $i++) {
            \App\Models\User::factory()->create([
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'name' => fake()->name(),
                'email_verified_at' => now(),
                'user_type' => 'user'
            ]);
        }
    }
}
