<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;

class UserPackageSeeder extends Seeder
{
    public function run(): void
    {
        // Evita duplicazioni su reseed
        if (UserPackage::query()->exists()) {
            $this->command?->warn('UserPackage ha già dati: salto creazione (idempotente).');
            return;
        }

        // Assicurati di avere i pacchetti
        if (Package::count() === 0) {
            $this->call(PackageSeeder::class);
        }
        $packages = Package::all(['id', 'name', 'total_lessons']);

        // Prendi i clienti
        $clients = User::role('cliente')->get(['id']);
        if ($clients->isEmpty()) {
            $this->command?->warn('Nessun utente con ruolo cliente: salto.');
            return;
        }

        $now = now();

        foreach ($clients as $client) {
            // 60% dei clienti hanno un pacchetto ATTIVO con crediti > 0
            if (random_int(1, 100) <= 60) {
                $pkg = $packages->random();
                $remaining = random_int(1, (int) $pkg->total_lessons); // almeno 1
                UserPackage::create([
                    'user_id' => $client->id,
                    'package_id' => $pkg->id,
                    'lessons_remaining' => $remaining,
                    'purchased_at' => $now->copy()->subDays(random_int(0, 90)),
                ]);
            }

            // 30% hanno un secondo pacchetto (può essere esaurito o ancora attivo)
            if (random_int(1, 100) <= 30) {
                $pkg = $packages->random();
                $remaining = random_int(0, (int) $pkg->total_lessons); // può essere 0 = scaduto
                UserPackage::create([
                    'user_id' => $client->id,
                    'package_id' => $pkg->id,
                    'lessons_remaining' => $remaining,
                    'purchased_at' => $now->copy()->subDays(random_int(30, 180)),
                ]);
            }
        }
    }
}
