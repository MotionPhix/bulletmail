<?php

namespace App\Models;

use App\Traits\HasTeamScope;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
  use HasTeamScope, HasUuid;

  protected $fillable = [
    'team_id',
    'user_id',
    'type',
    'category',
    'settings',
    'metadata'
  ];

  protected $casts = [
    'settings' => 'array',
    'metadata' => 'array'
  ];

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public static function getTeamSettings(Team $team, string $category, $default = []): array
  {
    return static::where('team_id', $team->id)
      ->where('type', 'team')
      ->where('category', $category)
      ->first()
      ?->settings ?? $default;
  }

  public static function getUserSettings(User $user, string $category, $default = []): array
  {
    return static::where('user_id', $user->id)
      ->where('type', 'user')
      ->where('category', $category)
      ->first()
      ?->settings ?? $default;
  }

  public static function updateTeamSettings(Team $team, string $category, array $settings): void
  {
    static::updateOrCreate(
      [
        'team_id' => $team->id,
        'type' => 'team',
        'category' => $category
      ],
      [
        'settings' => $settings
      ]
    );
  }

  public static function updateUserSettings(User $user, string $category, array $settings): void
  {
    static::updateOrCreate(
      [
        'user_id' => $user->id,
        'type' => 'user',
        'category' => $category
      ],
      [
        'settings' => $settings
      ]
    );
  }

  // Default settings templates
  public static function getDefaultEmailSettings(): array
  {
    return [
      'from_name' => null,
      'from_email' => null,
      'reply_to' => null,
      'footer_text' => null
    ];
  }

  public static function getDefaultBrandingSettings(): array
  {
    return [
      'logo_url' => null,
      'colors' => [
        'primary' => '#4F46E5',
        'secondary' => '#7C3AED'
      ],
      'email_header' => null,
      'email_footer' => null
    ];
  }

  public static function getDefaultQuotaSettings(): array
  {
    return [
      'subscriber_limit' => 1000,
      'campaign_limit' => 100,
      'monthly_email_limit' => 10000,
      'daily_email_limit' => 1000
    ];
  }
}
