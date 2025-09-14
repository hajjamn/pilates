<?php

namespace Database\Seeders;

use App\Models\DigitalLesson;
use App\Models\DigitalLessonUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DigitalLessonUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('cliente')->get();
        $digitalLessons = DigitalLesson::all();

        foreach ($digitalLessons as $digitalLesson) {
            $selectedUsers = $users->random(rand(5, 15));

            foreach ($selectedUsers as $user) {
                DigitalLessonUser::firstOrCreate([
                    'digital_lesson_id' => $digitalLesson->id,
                    'user_id' => $user->id,
                ], [
                    'unlocked_at' => now()->subDays(rand(0, 60))
                ]);
            }
        }
    }
}
