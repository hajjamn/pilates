<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            OperatorSeeder::class,
            ClientSeeder::class,
            RoomSeeder::class,
            MachineSeeder::class,
            WeeklyAvailabilitySeeder::class,
            PackageSeeder::class,
            LessonSeeder::class,
            UserPackageSeeder::class,
            LessonUserSeeder::class,
            DigitalLessonSeeder::class,
            DigitalLessonUserSeeder::class,
        ]);
    }
}
