<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        $operators = [

            [
                'first_name' => 'Ada',
                'last_name' => 'Turco',
                'email' => 'ada.turco@gmail.com',
                'phone' => '+393345749496',
                'birth_date' => '',
                'roles' => ['admin', 'operatore'],
            ],
            [
                'first_name' => 'Mariapia',
                'last_name' => 'Manniello',
                'email' => 'mariapiamanniello15@gmail.com',
                'phone' => '+393318119543',
                'birth_date' => '',
                'roles' => ['operatore'],
            ],
            [
                'first_name' => 'MariaRita',
                'last_name' => 'Circondato',
                'email' => 'circondatomariarita22@gmail.com',
                'phone' => '+393921206162',
                'birth_date' => '',
                'roles' => ['operatore'],
            ],
            [
                'first_name' => 'Giulia',
                'last_name' => 'Mariniello',
                'email' => 'giuliamariniello00@gmail.com',
                'phone' => '+393348379521',
                'birth_date' => '',
                'roles' => ['operatore'],
            ],
            [
                'first_name' => 'Lucrezia',
                'last_name' => 'Violante',
                'email' => 'lucreziaviolante99@gmail.com',
                'phone' => '+393393781045',
                'birth_date' => '',
                'roles' => ['operatore'],
            ],

        ];

        foreach ($operators as $op) {
            $user = User::updateOrCreate(
                ['email' => $op['email']],
                [
                    'first_name' => $op['first_name'],
                    'last_name' => $op['last_name'],
                    'phone' => $op['phone'],
                    'birth_date' => !empty($op['birth_date']) ? $op['birth_date'] : null,
                    'password' => Hash::make('1234'),
                    'email_verified_at' => now(),
                ]
            );

            foreach ($op['roles'] as $role) {
                if (!$user->hasRole($role)) {
                    $user->assignRole($role);
                }
            }
        }
    }
}
