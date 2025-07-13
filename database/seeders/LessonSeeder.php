<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\WeeklyAvailability;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        //$startDate = Carbon::today();
        //$endDate = $startDate->copy()->addMonth();

        $startDate = Carbon::today()->subWeeks(2);
        $endDate = $startDate->copy()->addWeeks(2);

        $availabilities = WeeklyAvailability::where('active', true)->get();

        foreach ($availabilities as $availability) {
            $date = $startDate->copy();

            while ($date->lte($endDate)) {
                if ($date->dayOfWeek === $availability->day_of_week) {
                    $startsAt = $date->copy()->setTimeFromTimeString($availability->start_time);

                    Lesson::create([
                        'room_id' => $availability->room_id,
                        'operator_id' => $availability->operator_id,
                        'starts_at' => $startsAt,
                        'max_clients' => 7,
                        'canceled' => false,
                    ]);
                }

                $date->addDay();
            }
        }
    }
}
