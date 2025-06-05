<?php

namespace Database\Factories;

use App\Models\{Team, User};
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamFactory extends Factory
{
  protected $model = Team::class;

  public function definition(): array
  {
    return [
      'uuid' => fake()->uuid(),
      'name' => $this->faker->company(),
      'owner_id' => User::factory(),
      'personal_team' => false
    ];
  }

  public function configure(): self
  {
    return $this->afterCreating(function (Team $team) {
      $team->settings()->create([
        'email_settings' => [
          'from_name' => $this->faker->name(),
          'from_email' => $this->faker->safeEmail(),
          'reply_to' => $this->faker->safeEmail()
        ],
        'branding' => [
          'logo_url' => null,
          'colors' => [
            'primary' => '#4F46E5',
            'secondary' => '#7C3AED'
          ]
        ]
      ]);
    });
  }

  public function personal(): self
  {
    return $this->state(function (array $attributes) {
      return [
        'personal_team' => true,
      ];
    });
  }

  public function withOwner(User $user): self
  {
    return $this->state(function (array $attributes) use ($user) {
      return [
        'owner_id' => $user->id,
      ];
    });
  }
}
