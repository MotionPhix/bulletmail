<?php

namespace Database\Factories;

use App\Models\SubscriptionRenewal;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionRenewalFactory extends Factory
{
  protected $model = SubscriptionRenewal::class;

  public function definition(): array
  {
    $completed = $this->faker->boolean(80); // 80% chance of being completed

    return [
      'uuid' => Str::uuid(),
      'subscription_id' => Subscription::factory(),
      'paychangu_reference' => 'PCG_' . Str::random(20),
      'amount' => $this->faker->randomElement([999, 2999, 4999, 9999]),
      'status' => $completed ? 'completed' : 'failed',
      'completed_at' => $completed ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
      'failed_at' => !$completed ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
    ];
  }

  /**
   * Indicate that the renewal was successful.
   */
  public function completed(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'status' => 'completed',
        'completed_at' => now(),
        'failed_at' => null,
      ];
    });
  }

  /**
   * Indicate that the renewal failed.
   */
  public function failed(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'status' => 'failed',
        'completed_at' => null,
        'failed_at' => now(),
      ];
    });
  }
}
