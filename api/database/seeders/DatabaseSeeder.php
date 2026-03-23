<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::factory()->create([
            'name'  => 'Admin',
            'email' => 'admin@paris-sportifs.test',
            'role'  => UserRole::ADMIN->value,
        ]);

        // 10 users
        $users = User::factory()->count(10)->create(['role' => UserRole::USER->value]);

        $this->call([
            SportSeeder::class,
            TeamSeeder::class,
            MatchSeeder::class,
            OddSeeder::class,
            BetSeeder::class,
        ]);
    }
}
