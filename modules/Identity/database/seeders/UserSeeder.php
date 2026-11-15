<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Identity\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
