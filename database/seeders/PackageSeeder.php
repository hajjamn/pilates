<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            ['name' => 'Lezione singola', 'total_lessons' => 1,  'price' => 25.00],
            ['name' => 'Pacchetto 5',     'total_lessons' => 5,  'price' => 110.00],
            ['name' => 'Pacchetto 10',    'total_lessons' => 10, 'price' => 200.00],
        ];

        foreach ($defs as $d) {
            Package::updateOrCreate(
                ['name' => $d['name']],
                ['total_lessons' => $d['total_lessons'], 'price' => $d['price']]
            );
        }
    }
}
