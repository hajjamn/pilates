<?php

namespace Database\Seeders;

//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\UserPackage;
use Illuminate\Database\Seeder;

class UserPackageSeeder extends Seeder
{
    public function run(): void
    {
        UserPackage::factory()->count(10)->create();
    }
}
