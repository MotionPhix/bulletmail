<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'user_id' => User::factory(),
      'email_from_address' =>  fake()->unique()->safeEmail,
      'email_from_name' =>  fake()->name,
      'sender_name' =>  fake()->name,
      'timezone' =>  fake()->timezone,
      'subscription_id' => Plan::factory(),
    ];
  }
}
