<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class; // * NOTE: just to be safe, generally when the Factory is not called EntityFactory and the Entity model is not in App\Models\Entity
    public function definition(): array
    {
        $first = $this->faker->firstName('male' | 'female');
        $last = $this->faker->lastName;
        $email = strtolower($first . '.' . $last . '.' . rand(1000, 9999) . '@gmail.com');

        return [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'password' => Hash::make('1234'),
            'phone' => $this->faker->numerify('+39 3## #######'), // Or numerify('+39 3## #######')
            'birth_date' => $this->faker->date('Y-m-d', '-18 years'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
