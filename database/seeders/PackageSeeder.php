<?php

namespace Database\Seeders;

//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => '4 lezioni base',
                'total_lessons' => 4,
                'price' => 60.00,
            ],
            [
                'name' => '10 lezioni intermedio',
                'total_lessons' => 10,
                'price' => 120.00,
            ],
            [
                'name' => '20 lezioni pro',
                'total_lessons' => 20,
                'price' => 220.00,
            ],
            [
                'name' => 'Pacchetto prova',
                'total_lessons' => 1,
                'price' => 10.00,
            ]
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate(['name' => $package['name']], $package);
        }
    }
}
