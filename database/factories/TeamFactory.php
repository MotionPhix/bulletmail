<?php

namespace Database\Factories;

use App\Models\{Team, User, Organization};
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
      'organization_id' => Organization::factory(),
      'personal_team' => false
    ];
  }

  public function withOrganization(Organization $organization): self
  {
    return $this->state(function (array $attributes) use ($organization) {
      return [
        'organization_id' => $organization->id,
        'name' => $organization->name,
      ];
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
