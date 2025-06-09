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
    $name = $this->faker->unique()->randomElement(['Starter', 'Pro', 'Business']);

    return [
      'uuid' => Str::uuid(),
      'name' => $name,
      'slug' => Str::slug($name),
      'description' => $this->faker->sentence(),
      'price' => $this->faker->randomElement([0, 2999, 4999]),
      'currency' => 'USD',
      'trial_days' => 14,
      'is_active' => true,
      'is_featured' => false,
      'sort_order' => 0,
      'features' => [
        'campaign_limit' => 10,
        'subscriber_limit' => 1000,
        'monthly_email_limit' => 10000,
        'daily_email_limit' => 1000,
        'can_schedule_campaigns' => true,
        'can_use_templates' => true,
        'can_import_subscribers' => true,
        'support_type' => 'email'
      ]
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
  public function free(): self
  {
    return $this->state([
      'name' => 'Free',
      'slug' => 'free',
      'price' => 0,
      'features' => [
        'campaign_limit' => 2,
        'subscriber_limit' => 500,
        'monthly_email_limit' => 2000,
        'daily_email_limit' => 200,
        'can_schedule_campaigns' => false,
        'can_use_templates' => true,
        'can_import_subscribers' => true,
        'support_type' => 'community'
      ]
    ]);
  }

  /**
   * Indicate that the plan is a pro plan.
   */
  public function pro(): self
  {
    return $this->state([
      'name' => 'Pro',
      'slug' => 'pro',
      'price' => 2999,
      'is_featured' => true,
      'features' => [
        'campaign_limit' => 50,
        'subscriber_limit' => 10000,
        'monthly_email_limit' => 50000,
        'daily_email_limit' => 5000,
        'can_schedule_campaigns' => true,
        'can_use_templates' => true,
        'can_import_subscribers' => true,
        'can_export_data' => true,
        'support_type' => 'priority'
      ]
    ]);
  }
}
