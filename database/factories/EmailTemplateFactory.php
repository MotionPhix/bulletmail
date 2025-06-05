<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Team;
use App\Models\User;
use App\Enums\TemplateCategory;
use App\Enums\TemplateType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
  protected $model = EmailTemplate::class;

  public function definition(): array
  {
    return [
      'team_id' => Team::factory(),
      'user_id' => User::factory(),
      'name' => $this->faker->words(3, true),
      'description' => $this->faker->sentence(),
      'subject' => $this->faker->sentence(),
      'content' => $this->faker->randomHtml(),
      'preview_text' => $this->faker->sentence(),
      'category' => $this->faker->randomElement(TemplateCategory::cases()),
      'type' => $this->faker->randomElement(TemplateType::cases()),
      'design' => [
        'layout' => 'default',
        'colors' => [
          'primary' => '#000000',
          'secondary' => '#ffffff'
        ]
      ],
      'variables' => [
        'company_name' => 'Example Inc.',
        'website_url' => 'https://example.com'
      ],
      'tags' => ['newsletter', 'marketing']
    ];
  }

  public function html(): self
  {
    return $this->state(fn(array $attributes) => [
      'type' => TemplateType::HTML
    ]);
  }

  public function marketing(): self
  {
    return $this->state(fn(array $attributes) => [
      'category' => TemplateCategory::MARKETING
    ]);
  }
}
