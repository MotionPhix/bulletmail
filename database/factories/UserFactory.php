<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
  /**
   * The current password being used by the factory.
   */
  protected static ?string $password;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $companyName = fake()->company();

    return [
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'email' => fake()->unique()->safeEmail(),
      'email_verified_at' => now(),
      'password' => static::$password ??= Hash::make('password'),
      'remember_token' => Str::random(10),
      'organization_name' => $companyName,
      'organization_size' => fake()->randomElement(['1-10', '11-50', '51-200', '201-500', '500+']),
      'industry' => fake()->randomElement([
        'technology',
        'e-commerce',
        'healthcare',
        'education',
        'finance',
        'marketing',
        'retail',
        'other'
      ]),
      'website' => 'https://www.' . Str::slug($companyName) . '.com',
      'account_status' => 'active',
      'current_team_id' => null,
      'onboarding_completed_at' => null,
    ];
  }

  /**
   * Indicate that the model's email address should be unverified.
   */
  public function unverified(): static
  {
    return $this->state(fn(array $attributes) => [
      'email_verified_at' => null,
    ]);
  }

  /**
   * Indicate that the user should have a personal team.
   */
  public function withPersonalTeam(?callable $callback = null): static
  {
    return $this->has(
      Team::factory()
        ->state(fn(array $attributes, User $user) => [
          'name' => $user->organization_name,
          'owner_id' => $user->id,
          'personal_team' => true,
        ])
        ->when(is_callable($callback), $callback),
      'ownedTeams'
    );
  }
}
