<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
  protected static ?string $password;

  public function definition(): array
  {
    return [
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => static::$password ??= Hash::make('password'),
      'remember_token' => Str::random(10),
      'account_status' => 'active',
      'current_team_id' => null,
      'preferences' => [
        'language' => 'en',
        'timezone' => 'UTC'
      ],
      'notification_settings' => [
        'email_notifications' => true,
        'browser_notifications' => true
      ]
    ];
  }

  public function unverified(): static
  {
    return $this->state(fn(array $attributes) => [
      'email_verified_at' => null,
    ]);
  }

  public function inactive(): static
  {
    return $this->state(fn(array $attributes) => [
      'account_status' => 'inactive',
    ]);
  }
}
