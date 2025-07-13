<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WeeklyAvailability;
//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WeeklyAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workHours = [
            '9:00:00',
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

        foreach ($workHours as $hour) {
            foreach (range(0, 6) as $dayOfWeek) {
                $operators = User::role('operatore')->inRandomOrder()->get();

                WeeklyAvailability::create([
                    'operator_id' => $operators[0]->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $hour,
                    'room_id' => 1,
                    'active' => true,
                ]);

                WeeklyAvailability::create([
                    'operator_id' => $operators[1]->id,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $hour,
                    'room_id' => 2,
                    'active' => true,
                ]);
            }
        }
    }
}
