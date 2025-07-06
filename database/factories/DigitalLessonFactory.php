<?php

namespace Database\Factories;

use App\Models\DigitalLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class DigitalLessonFactory extends Factory
{
    protected $model = DigitalLesson::class;
    public function definition(): array
    {
        return [
            'title' => 'Lezione - ' . $this->faker->words(2, true),
            'video_url' => $this->faker->url(),
            'price' => $this->faker->randomFloat(2, 5, 30),
        ];
    }
}
