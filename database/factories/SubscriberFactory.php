<?php

namespace Database\Factories;

use App\Models\Subscriber;
use App\Models\Team;
use App\Models\User;
use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriberFactory extends Factory
{
  protected $model = Subscriber::class;

  public function definition(): array
  {
    return [
      'team_id' => Team::factory(),
      'user_id' => User::factory(),
      'email' => $this->faker->unique()->safeEmail(),
      'first_name' => $this->faker->firstName(),
      'last_name' => $this->faker->lastName(),
      'custom_fields' => [
        'company' => $this->faker->company(),
        'phone' => $this->faker->phoneNumber(),
        'country' => $this->faker->country()
      ],
      'status' => SubscriberStatus::SUBSCRIBED,
      'subscribed_at' => now()->subDays(rand(1, 365)),
      'source' => $this->faker->randomElement(['import', 'form', 'api']),
      'ip_address' => $this->faker->ipv4(),
      'metadata' => [
        'browser' => $this->faker->userAgent(),
        'referrer' => $this->faker->url()
      ],
      'emails_received' => rand(0, 50),
      'emails_opened' => rand(0, 30),
      'emails_clicked' => rand(0, 20)
    ];
  }

  public function unsubscribed(): self
  {
    return $this->state(fn(array $attributes) => [
      'status' => SubscriberStatus::UNSUBSCRIBED,
      'unsubscribed_at' => now()->subDays(rand(1, 30)),
      'unsubscribe_reason' => $this->faker->randomElement([
        'No longer interested',
        'Too many emails',
        'Content not relevant',
        'Never subscribed'
      ])
    ]);
  }

  public function engaged(): self
  {
    return $this->state(fn(array $attributes) => [
      'emails_received' => rand(10, 50),
      'emails_opened' => rand(8, 40),
      'emails_clicked' => rand(5, 30),
      'last_opened_at' => now()->subDays(rand(1, 7)),
      'last_clicked_at' => now()->subDays(rand(1, 7))
    ]);
  }
}
