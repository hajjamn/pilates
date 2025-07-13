<?php

namespace Database\Seeders;

//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Lesson;
use App\Models\LessonUser;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Seeder;

class LessonUserSeeder extends Seeder
{
    public function run(): void
    {
        Lesson::past()->notCanceled()->each(function ($lesson) {
            if (rand(1, 10) === 10) {
                $users = User::role('cliente')->inRandomOrder()->take(rand(3, 6))->get();
            } else {
                $users = User::role('cliente')->inRandomOrder()->take(7)->get();
            }


            foreach ($users as $user) {
                $userPackage = UserPackage::where('user_id', $user->id)->active()->inRandomOrder()->first();
                if ($userPackage) {

                    $userPackage->decrement('lessons_remaining');

                    LessonUser::create([
                        'lesson_id' => $lesson->id,
                        'user_id' => $user->id,
                        'attended' => true,
                        'counted' => true,
                        'paid' => true,
                        'user_package_id' => $userPackage->id,
                    ]);

                } else {

                    LessonUser::create([
                        'lesson_id' => $lesson->id,
                        'user_id' => $user->id,
                        'attended' => true,
                        'counted' => false,
                        'paid' => true,
                        'paid_to_user_id' => User::role('operatore')->inRandomOrder()->first()->id,
                    ]);
                }
            }
        });

        Lesson::future()->notCanceled()->each(function ($lesson) {
            if (rand(1, 10) <= 7) {
                $users = User::role('cliente')->inRandomOrder()->take(rand(3, 6))->get();
            } else {
                $users = User::role('cliente')->inRandomOrder()->take(7)->get();
            }


            foreach ($users as $user) {
                $userPackage = UserPackage::where('user_id', $user->id)->active()->inRandomOrder()->first();
                if ($userPackage) {

                    $userPackage->decrement('lessons_remaining');

                    LessonUser::create([
                        'lesson_id' => $lesson->id,
                        'user_id' => $user->id,
                        'attended' => false,
                        'counted' => true,
                        'paid' => true,
                        'user_package_id' => $userPackage->id,
                    ]);

                } else {
                    LessonUser::create([
                        'lesson_id' => $lesson->id,
                        'user_id' => $user->id,
                        'attended' => false,
                        'counted' => false,
                        'paid' => true,
                        'paid_to_user_id' => User::role('operatore')->inRandomOrder()->first()->id,
                    ]);
                }
            }
        });

    }
}