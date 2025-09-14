<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(100)
            ->create()
            ->each(function ($user) {
                $user->assignRole('cliente');
            });
    }
}
