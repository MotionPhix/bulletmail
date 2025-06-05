<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipient>
 */
class RecipientFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $gender = fake()->randomElement(['female', 'male', 'unspecified']);

    return [
      'email' => fake()->unique()->safeEmail(),
      'name' => fake('ZA')->name($gender),
      'gender' => $gender
    ];
  }
}
