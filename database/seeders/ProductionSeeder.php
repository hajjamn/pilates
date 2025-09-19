<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
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
            RoomSeeder::class,
            WeeklyAvailabilitySeeder::class,
            PackageSeeder::class,
        ]);
    }

    /* 

    cd /var/www/generazionedigitaleprogrammi/adaturcopilates
git stash
git pull origin main
git stash pop
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache

    */

    /* 

    rm -rf public/build
    npm ci
    npm run build
    php artisan optimize:clear

    */
}
