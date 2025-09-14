<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\Room;
use App\Models\WeeklyAvailability;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::today()->subWeeks(2)->startOfDay();
        $endDate = Carbon::today()->addWeeks(2)->endOfDay();

        $roomMaxById = Room::query()->pluck('max_clients', 'id');

        $availabilities = WeeklyAvailability::query()
            ->where('active', true)
            ->get();

        foreach ($availabilities as $availability) {
            $roomId = (int) $availability->room_id;
            $maxClients = (int) ($roomMaxById[$roomId] ?? 0);

            if ($maxClients <= 0) {
                $this->command?->warn("Room {$roomId} con max_clients non valido. Salto le lezioni per questa availability.");
                continue;
            }

            $weekdayIso = ((int) $availability->day_of_week) + 1;

            $startDow = (int) $startDate->dayOfWeekIso;      // 1..7
            $diffDays = ($weekdayIso - $startDow + 7) % 7;   // 0..6
            $first = $startDate->copy()->addDays($diffDays);

            for ($date = $first->copy(); $date->lte($endDate); $date->addWeek()) {
                $startsAt = $date->copy()->setTimeFromTimeString($availability->start_time);

                Lesson::firstOrCreate(
                    [
                        'room_id' => $roomId,
                        'operator_id' => $availability->operator_id,
                        'starts_at' => $startsAt,
                    ],
                    [
                        'max_clients' => $maxClients,
                        'canceled' => false,
                        'manual_override' => false,
                    ]
                );
            }
        }
    }
}
