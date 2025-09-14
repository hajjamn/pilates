<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $defs = [
            ['name' => 'Lezione singola', 'total_lessons' => 1, 'price' => 25.00],
            ['name' => 'Pacchetto Basic', 'total_lessons' => 8, 'price' => 110.00],
        ];

        foreach ($defs as $d) {
            Package::updateOrCreate(
                ['name' => $d['name']],
                ['total_lessons' => $d['total_lessons'], 'price' => $d['price']]
            );
        }
    }
}
