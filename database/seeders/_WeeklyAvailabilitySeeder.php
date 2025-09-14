<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WeeklyAvailability;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class _WeeklyAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        // Ore piene 09:00 .. 20:00
        $workHours = [
            '09:00:00',
            '10:00:00',
            '11:00:00',
            '12:00:00',
            '13:00:00',
            '14:00:00',
            '15:00:00',
            '16:00:00',
            '17:00:00',
            '18:00:00',
            '19:00:00',
            '20:00:00'
        ];

        // Prendi TUTTI gli operatori (niente take(2))
        $operatorIds = User::role('operatore')->pluck('id')->values();

        if ($operatorIds->isEmpty()) {
            $this->command?->warn('Nessun operatore trovato: crea almeno 1 utente con ruolo "operatore".');
            return;
        }

        $rooms = [1, 2]; // Sala A, Sala B

        foreach (range(0, 6) as $dayOfWeek) { // 0=Lun … 6=Dom
            foreach ($workHours as $hour) {
                $end = Carbon::createFromFormat('H:i:s', $hour)->addHour()->format('H:i:s');

                // Mescola per QUESTA cella (giorno+ora)
                $shuffled = $operatorIds->shuffle()->values();
                $slotsToCreate = min(count($rooms), $shuffled->count()); // max 2

                for ($i = 0; $i < $slotsToCreate; $i++) {
                    $operatorId = $shuffled[$i];
                    $roomId = $rooms[$i]; // primo → Sala A, secondo → Sala B

                    WeeklyAvailability::firstOrCreate(
                        [
                            'operator_id' => $operatorId,
                            'day_of_week' => $dayOfWeek,
                            'start_time' => $hour,
                        ],
                        [
                            'end_time' => $end,
                            'room_id' => $roomId,
                            'active' => true,
                        ]
                    );
                }
            }
        }
    }
}
