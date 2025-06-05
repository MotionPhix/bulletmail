<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Team;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
  protected $model = Campaign::class;

  public function definition(): array
  {
    return [
      'team_id' => Team::factory(),
      'user_id' => User::factory(),
      'template_id' => EmailTemplate::factory(),
      'name' => $this->faker->words(3, true),
      'description' => $this->faker->sentence(),
      'subject' => $this->faker->sentence(),
      'content' => $this->faker->randomHtml(),
      'preview_text' => $this->faker->sentence(),
      'from_name' => $this->faker->name(),
      'from_email' => $this->faker->safeEmail(),
      'reply_to' => $this->faker->safeEmail(),
      'status' => CampaignStatus::DRAFT,
      'recipient_lists' => [],
      'recipient_segments' => [],
      'total_recipients' => 0,
      'sendgrid_settings' => [
        'track_opens' => true,
        'track_clicks' => true,
        'footer' => [
          'enabled' => true,
          'html' => '<p>Footer content</p>'
        ]
      ]
    ];
  }

  public function scheduled(): self
  {
    return $this->state(fn(array $attributes) => [
      'status' => CampaignStatus::SCHEDULED,
      'scheduled_at' => now()->addDays(rand(1, 7))
    ]);
  }

  public function withRecipients(int $count = 100): self
  {
    return $this->state(fn(array $attributes) => [
      'total_recipients' => $count
    ]);
  }
}
