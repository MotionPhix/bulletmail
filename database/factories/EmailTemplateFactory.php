<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Team;
use App\Enums\EmailTemplateCategory;
use App\Enums\EmailTemplateType;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailTemplateFactory extends Factory
{
  protected $model = EmailTemplate::class;

  public function definition(): array
  {
    $team = Team::factory()->create();

    return [
      'team_id' => $team->id,
      'user_id' => $team->owner_id,
      'name' => $this->faker->words(3, true),
      'description' => $this->faker->sentence(),
      'subject' => $this->faker->sentence(),
      'content' => $this->faker->randomHtml(),
      'preview_text' => $this->faker->sentence(),
      'category' => $this->faker->randomElement(EmailTemplateCategory::cases()),
      'type' => $this->faker->randomElement(EmailTemplateType::cases()),
      'design' => [
        'counters' => [
          'u_row' => 13,
          'u_column' => 16,
          'u_content_menu' => 3,
          'u_content_text' => 11,
          'u_content_image' => 3,
          'u_content_button' => 4,
          'u_content_social' => 1,
          'u_content_divider' => 6
        ],
        'body' => [
          'rows' => [[
            'cells' => [1],
            'columns' => [[
              'contents' => [[
                'type' => 'menu',
                'values' => [
                  'containerPadding' => '0px',
                  '_meta' => [
                    'htmlID' => 'u_content_menu_3',
                    'htmlClassNames' => 'u_content_menu'
                  ],
                  'selectable' => true,
                  'draggable' => true,
                  'duplicatable' => true,
                  'deletable' => true,
                  'backgroundColor' => '#ffffff',
                  'textAlign' => 'center',
                  'hideDesktop' => false,
                  'hideMobile' => false,
                  'items' => [
                    ['label' => '{{ company_name }}', 'url' => '{{ website_url }}', '_meta' => ['htmlID' => 'u_menu_item_1']],
                    ['label' => '{{ company_name }}', 'url' => '{{ website_url }}', '_meta' => ['htmlID' => 'u_menu_item_2']]
                  ]
                ]
              ]],
              'values' => [
                '_meta' => [
                  'htmlID' => 'u_column_1',
                  'htmlClassNames' => 'u_column'
                ],
                'border' => [],
                'padding' => '0px',
                'backgroundColor' => ''
              ]
            ]]
          ]],
          'values' => [
            'backgroundColor' => '#ffffff',
            'containerPadding' => '0px',
            'fontFamily' => [
              'label' => 'Geist Mono',
              'value' => '"Geist Mono",monospace'
            ]
          ],
        ],
        'schemaVersion' => 5,
      ],
      'merge_tags' => [
        'company_name' => 'Example Inc.',
        'website_url' => 'https://example.com'
      ],
      'tags' => ['newsletter', 'marketing']
    ];
  }

  public function html(): self
  {
    return $this->state(fn(array $attributes) => [
      'type' => EmailTemplateType::HTML
    ]);
  }

  public function marketing(): self
  {
    return $this->state(fn(array $attributes) => [
      'category' => EmailTemplateCategory::MARKETING
    ]);
  }

  public function forTeam(Team $team): self
  {
    return $this->state(function (array $attributes) use ($team) {
      return [
        'team_id' => $team->id,
        'user_id' => $team->owner_id
      ];
    });
  }
}
