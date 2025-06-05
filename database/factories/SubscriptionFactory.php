<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionFactory extends Factory
{
  protected $model = Subscription::class;

  public function definition(): array
  {
    $starts_at = $this->faker->dateTimeBetween('-1 year', 'now');
    $trial_ends_at = date('Y-m-d H:i:s', strtotime('+14 days', strtotime($starts_at->format('Y-m-d H:i:s'))));
    $ends_at = date('Y-m-d H:i:s', strtotime('+1 year', strtotime($starts_at->format('Y-m-d H:i:s'))));

    return [
      'uuid' => Str::uuid(),
      'user_id' => User::factory(),
      'plan_id' => Plan::factory(),
      'status' => $this->faker->randomElement([
        Subscription::STATUS_ACTIVE,
        Subscription::STATUS_TRIAL,
        Subscription::STATUS_CANCELLED,
        Subscription::STATUS_EXPIRED,
        Subscription::STATUS_PENDING
      ]),
      'paychangu_reference' => 'PCG_' . Str::random(20),
      'starts_at' => $starts_at,
      'ends_at' => $ends_at,
      'trial_ends_at' => $trial_ends_at,
      'cancelled_at' => null,
      'last_payment_at' => $starts_at,
      'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'mobile_money'])
    ];
  }

  /**
   * Indicate that the subscription is active.
   */
  public function active(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'status' => Subscription::STATUS_ACTIVE,
        'cancelled_at' => null,
        'ends_at' => now()->addYear(),
      ];
    });
  }

  /**
   * Indicate that the subscription is on trial.
   */
  public function trial(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'status' => Subscription::STATUS_TRIAL,
        'trial_ends_at' => now()->addDays(14),
        'ends_at' => now()->addYear(),
      ];
    });
  }

  /**
   * Indicate that the subscription is cancelled.
   */
  public function cancelled(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'status' => Subscription::STATUS_CANCELLED,
        'cancelled_at' => now(),
        'ends_at' => now()->addDays(30), // Grace period
      ];
    });
  }
}
