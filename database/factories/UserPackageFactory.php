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
        // prendi un pacchetto esistente (oppure creane uno se non c'è)
        $package = Package::inRandomOrder()->first() ?? Package::factory()->create();

        // prendi un cliente a caso (oppure creane uno e assegnagli il ruolo cliente)
        $user = User::role('cliente')->inRandomOrder()->first();
        if (!$user) {
            $user = User::factory()->create();
            $user->assignRole('cliente');
        }

        // 70% attivo (1..total_lessons), 30% esaurito (0)
        $remaining = fake()->boolean(70)
            ? fake()->numberBetween(1, max(1, (int) $package->total_lessons))
            : 0;

        return [
            'user_id' => $user->id,
            'package_id' => $package->id,
            'lessons_remaining' => $remaining,
            'purchased_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    // Stati comodi (se vuoi forzare)
    public function active(): self
    {
        return $this->state(function (array $attributes) {
            $pkg = Package::find($attributes['package_id']) ?? Package::inRandomOrder()->first();
            $max = $pkg ? (int) $pkg->total_lessons : 10;
            return ['lessons_remaining' => fake()->numberBetween(1, max(1, $max))];
        });
    }

    public function exhausted(): self
    {
        return $this->state(fn() => ['lessons_remaining' => 0]);
    }

    public function forUser(User $user): self
    {
        return $this->state(fn() => ['user_id' => $user->id]);
    }

    public function forPackage(Package $package): self
    {
        return $this->state(fn() => ['package_id' => $package->id]);
    }
}
