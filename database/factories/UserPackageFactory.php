<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPackageFactory extends Factory
{
    protected $model = UserPackage::class;
    public function definition(): array
    {
        $package = Package::inRandomOrder()->first();

        return [
            'user_id' => User::role('cliente')->inRandomOrder()->first()->id,
            'package_id' => $package->id,
            'lessons_remaining' => $package->total_lessons,
            'purchased_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
