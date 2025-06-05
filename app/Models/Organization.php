<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Organization extends Model implements HasMedia
{
  use HasUuid, SoftDeletes, InteractsWithMedia;

  protected $fillable = [
    'name',
    'size',
    'industry',
    'website',
    'phone',
    'primary_color',
    'secondary_color',
    'email_header',
    'email_footer',
    'default_from_name',
    'default_from_email',
    'default_reply_to',
    'subscriber_limit',
    'campaign_limit',
    'monthly_email_limit',
    'daily_email_limit'
  ];

  protected $casts = [
    'subscriber_limit' => 'integer',
    'campaign_limit' => 'integer',
    'monthly_email_limit' => 'integer',
    'daily_email_limit' => 'integer'
  ];

  public function teams(): HasMany
  {
    return $this->hasMany(Team::class);
  }

  public function registerMediaCollections(): void
  {
    $this->addMediaCollection('logo')
      ->singleFile()
      ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml'])
      ->registerMediaConversions(function (Media $media) {
        $this->addMediaConversion('thumb')
          ->width(100)
          ->height(100);

        $this->addMediaConversion('email')
          ->width(200)
          ->height(50);
      });
  }

  public function getBrandingConfig(): array
  {
    return [
      'colors' => [
        'primary' => $this->primary_color,
        'secondary' => $this->secondary_color
      ],
      'email_header' => $this->email_header,
      'email_footer' => $this->email_footer
    ];
  }

  public function getEmailConfig(): array
  {
    return [
      'from_name' => $this->default_from_name,
      'from_email' => $this->default_from_email,
      'reply_to' => $this->default_reply_to
    ];
  }

  public function getQuotaLimits(): array
  {
    return [
      'subscriber_limit' => $this->subscriber_limit,
      'campaign_limit' => $this->campaign_limit,
      'monthly_email_limit' => $this->monthly_email_limit,
      'daily_email_limit' => $this->daily_email_limit
    ];
  }
}
