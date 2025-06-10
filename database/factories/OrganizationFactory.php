<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganizationFactory extends Factory
{
  protected $model = Organization::class;

  public function definition(): array
  {
    $name = $this->faker->company();

    return [
      'uuid' => Str::uuid(),
      'name' => $name,
      'size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-500', '501+']),
      'industry' => $this->faker->randomElement([
        'technology',
        'healthcare',
        'finance',
        'education',
        'retail',
        'manufacturing',
        'other'
      ]),
      'website' => $this->faker->url(),
      'phone' => $this->faker->phoneNumber(),

      // Branding
      'primary_color' => '#4F46E5',
      'secondary_color' => '#7C3AED',
      'email_header' => null,
      'email_footer' => null,

      // Email Settings
      'default_from_name' => fn(array $attrs) => $attrs['name'],
      'default_from_email' => fn(array $attrs) =>
      'no-reply@' . Str::slug($attrs['name']) . '.com',
      'default_reply_to' => fn(array $attrs) =>
      'support@' . Str::slug($attrs['name']) . '.com',

      // JSON fields
      'settings' => [],
      'preferences' => [
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d'
      ],
      'integrations' => [],
      'metadata' => [],

      'owner_id' => User::factory()
    ];
  }

  public function withOwner(User $owner): self
  {
    return $this->state(fn(array $attributes) => [
      'owner_id' => $owner->id
    ]);
  }

  public function configure()
  {
    return $this->afterCreating(function (Organization $organization) {
      if (!$organization->teams()->exists()) {
        Team::factory()->create([
          'organization_id' => $organization->id,
          'owner_id' => $organization->owner_id,
          'name' => $organization->name . ' Team',
          'is_default' => true,
          'personal_team' => true
        ]);
      }
    });
  }
}
