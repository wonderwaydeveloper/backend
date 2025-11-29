<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+989' . fake()->numerify('#########'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('password'),
            'bio' => fake()->boolean(30) ? fake()->paragraph() : null,
            'avatar' => null,
            'cover_image' => null,
            'website' => fake()->boolean(20) ? fake()->url() : null,
            'location' => fake()->boolean(50) ? fake()->city() : null,
            'birth_date' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'is_underage' => false,
            'is_private' => fake()->boolean(20),
            'is_verified' => fake()->boolean(10),
            'is_banned' => false,
            'two_factor_enabled' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    public function underage(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_date' => fake()->dateTimeBetween('-17 years', '-10 years')->format('Y-m-d'),
            'is_underage' => true,
        ]);
    }
}