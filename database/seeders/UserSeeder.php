<?php

namespace Database\Seeders;

//use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'first_name' => 'Ada',
            'last_name' => 'Cognome',
            'email' => 'ada.cognome@gmail.com',
            'password' => Hash::make('1234'),
            'phone' => '+39 300 0000000',
            'birth_date' => '1980-01-01',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        $admin->assignRole(['admin', 'operatore']);

        User::factory()->count(6)->create()->each(function ($user) {
            $user->assignRole('operatore');
        });

        User::factory()->count(100)->create()->each(function ($user) {
            $user->assignRole('cliente');
        });
    }
}
