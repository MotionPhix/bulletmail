<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamSetting extends Model
{
  protected $fillable = [
    'team_id',
    'email_settings',
    'branding',
    'quotas',
    'notifications',
    'marketing',
    'company',
    'sender'
  ];

  protected $casts = [
    'email_settings' => 'array',
    'branding' => 'array',
    'quotas' => 'array',
    'notifications' => 'array',
    'marketing' => 'array',
    'company' => 'array',
    'sender' => 'array'
  ];

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function getDefaultEmailSettings(): array
  {
    return [
      'from_name' => null,
      'from_email' => null,
      'reply_to' => null,
      'footer_text' => null
    ];
  }

  public function getDefaultBrandingSettings(): array
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

  public function getDefaultQuotaSettings(): array
  {
    return [
      'subscriber_limit' => 1000,
      'campaign_limit' => 100,
      'monthly_email_limit' => 10000,
      'daily_email_limit' => 1000
    ];
  }

  protected static function booted()
  {
    static::creating(function ($settings) {
      if (empty($settings->email_settings)) {
        $settings->email_settings = $settings->getDefaultEmailSettings();
      }
      if (empty($settings->branding)) {
        $settings->branding = $settings->getDefaultBrandingSettings();
      }
      if (empty($settings->quotas)) {
        $settings->quotas = $settings->getDefaultQuotaSettings();
      }
    });
  }
}
