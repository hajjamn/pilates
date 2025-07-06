<?php

namespace Database\Seeders;

use App\Models\DigitalLesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DigitalLessonSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = DigitalLesson::factory()->count(10)->create();
    }
}
