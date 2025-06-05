<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
  protected $model = Plan::class;

  public function definition(): array
  {
    $name = $this->faker->unique()->randomElement([
      'Free Plan',
      'Starter Plan',
      'Pro Plan',
      'Business Plan',
      'Enterprise Plan'
    ]);

    return [
      'uuid' => Str::uuid(),
      'name' => $name,
      'slug' => Str::slug($name),
      'description' => $this->faker->paragraph(),
      'price' => $this->faker->randomElement([0, 999, 2999, 4999, 9999]),
      'currency' => 'MWK',
      'trial_days' => 14,
      'is_active' => true,
      'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
      'sort_order' => $this->faker->numberBetween(0, 10),
      'features' => [
        'campaign_limit' => $this->faker->randomElement([2, 5, 10, 20, 'unlimited']),
        'email_limit' => $this->faker->randomElement(['1,000', '5,000', '10,000', '50,000', 'unlimited']),
        'recipient_limit' => $this->faker->randomElement(['500', '1,000', '5,000', '10,000', 'unlimited']),
        'segment_limit' => $this->faker->randomElement([2, 5, 10, 20, 'unlimited']),
        'can_schedule_campaigns' => $this->faker->boolean(80), // 80% chance of true
        'personalisation' => $this->faker->boolean(70), // 70% chance of true
        'analytics' => $this->faker->randomElement(['Basic analytics', 'Advanced analytics', 'Premium analytics']),
        'support_type' => $this->faker->randomElement(['Email support', '24/7 Email support', 'Priority support', 'Dedicated support'])
      ],
      'metadata' => null
    ];
  }

  /**
   * Configure the model factory.
   *
   * @return $this
   */
  public function configure()
  {
    return $this->afterMaking(function (Plan $plan) {
      // Additional setup after making
    })->afterCreating(function (Plan $plan) {
      // Additional setup after creating
    });
  }

  /**
   * Indicate that the plan is a free plan.
   */
  public function free(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'name' => 'Free Plan',
        'price' => 0,
        'features' => [
          'campaign_limit' => 2,
          'email_limit' => '1,000',
          'recipient_limit' => '500',
          'segment_limit' => 2,
          'can_schedule_campaigns' => false,
          'personalisation' => false,
          'analytics' => 'Basic analytics',
          'support_type' => 'Email support'
        ]
      ];
    });
  }

  /**
   * Indicate that the plan is a pro plan.
   */
  public function pro(): Factory
  {
    return $this->state(function (array $attributes) {
      return [
        'name' => 'Pro Plan',
        'price' => 4999,
        'is_featured' => true,
        'features' => [
          'campaign_limit' => 20,
          'email_limit' => '50,000',
          'recipient_limit' => '10,000',
          'segment_limit' => 10,
          'can_schedule_campaigns' => true,
          'personalisation' => true,
          'analytics' => 'Advanced analytics',
          'support_type' => 'Priority support'
        ]
      ];
    });
  }
}
