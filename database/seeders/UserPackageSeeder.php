<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Seeder;

class UserPackageSeeder extends Seeder
{
    public function run(): void
    {
        // assicurati di avere qualche pacchetto
        if (Package::count() === 0) {
            \App\Models\Package::factory()->count(3)->create();
        }

        $clients = User::role('cliente')->get();
        if ($clients->isEmpty()) {
            return;
        }

        foreach ($clients as $client) {
            // 45% dei clienti riceve un pacchetto
            if (fake()->boolean(45)) {
                $package = Package::inRandomOrder()->first();

                // 70% attivo, 30% esaurito
                $isActive = fake()->boolean(70);
                $remaining = $isActive
                    ? fake()->numberBetween(1, max(1, (int) $package->total_lessons))
                    : 0;

                UserPackage::factory()
                    ->forUser($client)
                    ->forPackage($package)
                    ->state(['lessons_remaining' => $remaining])
                    ->create();
            }
        }
    }
}
