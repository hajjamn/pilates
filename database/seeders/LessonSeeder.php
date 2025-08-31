<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\WeeklyAvailability;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        // Da 2 settimane fa a 2 settimane avanti
        $startDate = Carbon::today()->subWeeks(2);
        $endDate = Carbon::today()->addWeeks(2);

        $availabilities = WeeklyAvailability::where('active', true)->get();

        foreach ($availabilities as $availability) {
            $date = $startDate->copy();

            while ($date->lte($endDate)) {
                // MAPPING CORRETTO: 0 = Lunedì … 6 = Domenica
                $dowZeroMonday = $date->dayOfWeekIso - 1;

                if ($dowZeroMonday === (int) $availability->day_of_week) {
                    $startsAt = $date->copy()->setTimeFromTimeString($availability->start_time);

                    Lesson::firstOrCreate(
                        [
                            'room_id' => $availability->room_id,
                            'operator_id' => $availability->operator_id,
                            'starts_at' => $startsAt,
                        ],
                        [
                            'max_clients' => 7,
                            'canceled' => false,
                            'manual_override' => false,
                        ]
                    );
                }

                $date->addDay();
            }
        }
    }
}
