<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@demo.com',
            'password' => Hash::make('demo123'),
        ]);
        \App\Models\User::factory(4)->create();
        $this->command->info('Seeded 5 users (admin@demo.com / demo123)');
    }
}
