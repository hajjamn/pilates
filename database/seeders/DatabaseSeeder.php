<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            RoomSeeder::class,
            MachineSeeder::class,
            WeeklyAvailabilitySeeder::class,
            LessonSeeder::class,
            PackageSeeder::class,
            UserPackageSeeder::class,
            LessonUserSeeder::class,
            DigitalLessonSeeder::class,
            DigitalLessonUserSeeder::class,
        ]);
    }
}
