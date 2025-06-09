<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Organization extends Model implements HasMedia
{
  use HasUuid, HasFactory, SoftDeletes, InteractsWithMedia;

  protected $fillable = [
    'name',
    'size',
    'industry',
    'website',
    'phone',
    'owner_id',
    'primary_color',
    'secondary_color',
    'email_header',
    'email_footer',
    'default_from_name',
    'default_from_email',
    'default_reply_to',
    'settings',
    'preferences',
    'integrations',
    'metadata'
  ];

  protected $casts = [
    'settings' => 'array',
    'preferences' => 'array',
    'integrations' => 'array',
    'metadata' => 'array'
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
      'email_footer' => $this->email_footer,
      // Add logo configuration
      'maxLogoSize' => 2 * 1024 * 1024, // 2MB in bytes
      'allowedTypes' => ['image/jpeg', 'image/png', 'image/svg+xml'],
      'minDimensions' => [
        'width' => 100,
        'height' => 100
      ],
      'maxDimensions' => [
        'width' => 1000,
        'height' => 1000
      ]
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

  // Add owner relationship
  public function owner()
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  // Add method to check if user is owner
  public function isOwnedBy(User $user): bool
  {
    return $this->owner_id === $user->id;
  }

  public function subscription(): HasOne
  {
    return $this->hasOne(Subscription::class)->latest();
  }

  public function subscriptions(): HasMany
  {
    return $this->hasMany(Subscription::class);
  }

  public function currentPlan(): ?Plan
  {
    return $this->subscription?->plan;
  }

  public function isOnPaidPlan(): bool
  {
    return $this->subscription?->isActive() && !$this->subscription?->plan?->isFreePlan();
  }

  public function getStats(): array
  {
    return [
      'teams_count' => $this->teams()->count(),
      'members_count' => $this->teams()->withCount('users')->get()->sum('users_count'),
      'subscribers_count' => $this->teams()->withCount('subscribers')->get()->sum('subscribers_count'),
      'campaigns_count' => $this->teams()->withCount('campaigns')->get()->sum('campaigns_count'),
      'sent_campaigns_count' => $this->teams()
        ->withCount(['campaigns' => fn($q) => $q->where('status', 'sent')])
        ->get()
        ->sum('campaigns_count'),
      /*'active_automations_count' => $this->teams()
        ->withCount(['automations' => fn($q) => $q->where('status', 'active')])
        ->get()
        ->sum('automations_count'),*/
    ];
  }

  public function getCampaignStats(): array
  {
    $teamIds = $this->teams()->pluck('id');

    $stats = Campaign::whereIn('team_id', $teamIds)
      ->join('campaign_stats', 'campaigns.id', '=', 'campaign_stats.campaign_id')
      ->selectRaw('SUM(campaign_stats.delivered_count) as total_sent')
      ->selectRaw('SUM(campaign_stats.opened_count) as total_opened')
      ->selectRaw('SUM(campaign_stats.clicked_count) as total_clicked')
      ->selectRaw('SUM(campaign_stats.bounced_count) as total_bounced')
      ->first();

    return [
      'total_sent' => $stats->total_sent ?? 0,
      'total_opened' => $stats->total_opened ?? 0,
      'total_clicked' => $stats->total_clicked ?? 0,
      'total_bounced' => $stats->total_bounced ?? 0,
    ];
  }

  public function getSubscriberGrowth(): array
  {
    return $this->teams()
      ->with(['subscribers' => fn($q) => $q->select('created_at', 'team_id')])
      ->get()
      ->flatMap->subscribers
      ->groupBy(fn($sub) => $sub->created_at->format('Y-m'))
      ->map(fn($subs) => $subs->count())
      ->toArray();
  }
}
