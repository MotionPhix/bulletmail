<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use App\Traits\{HasTeamScope, HasUuid};
use Illuminate\Database\Eloquent\{Attributes\Scope, Builder, Casts\Attribute, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Subscriber extends Model
{
  use HasFactory, HasUuid, HasTeamScope, SoftDeletes;

  protected $fillable = [
    'team_id',
    'user_id',
    'email',
    'first_name',
    'last_name',
    'custom_fields',
    'status',
    'subscribed_at',
    'unsubscribed_at',
    'unsubscribe_reason',
    'source',
    'ip_address',
    'metadata',
    'last_emailed_at',
    'last_opened_at',
    'last_clicked_at',
    'emails_received',
    'emails_opened',
    'emails_clicked'
  ];

  protected $casts = [
    'custom_fields' => 'array',
    'metadata' => 'array',
    'status' => SubscriberStatus::class,
    'subscribed_at' => 'datetime',
    'unsubscribed_at' => 'datetime',
    'last_emailed_at' => 'datetime',
    'last_opened_at' => 'datetime',
    'last_clicked_at' => 'datetime',
    'emails_received' => 'integer',
    'emails_opened' => 'integer',
    'emails_clicked' => 'integer'
  ];

  // Relationships
  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function mailingLists(): BelongsToMany
  {
    return $this->belongsToMany(MailingList::class)
      ->withPivot(['status', 'subscribed_at', 'unsubscribed_at', 'metadata'])
      ->withTimestamps();
  }

  public function events(): HasMany
  {
    return $this->hasMany(CampaignEvent::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  // Scopes
  public function scopeSubscribed($query)
  {
    return $query->where('status', SubscriberStatus::SUBSCRIBED);
  }

  public function scopeUnsubscribed($query)
  {
    return $query->where('status', SubscriberStatus::UNSUBSCRIBED);
  }

  public function scopeBounced($query)
  {
    return $query->where('status', SubscriberStatus::BOUNCED);
  }

  // Additional scopes
  public function scopeActive($query)
  {
    return $query->where('status', SubscriberStatus::SUBSCRIBED);
  }

  public function scopeInactive($query)
  {
    return $query->whereNot('status', SubscriberStatus::SUBSCRIBED);
  }

  public function scopeEngaged($query, $days = 30)
  {
    return $query->where(function ($q) use ($days) {
      $q->whereDate('last_opened_at', '>=', now()->subDays($days))
        ->orWhereDate('last_clicked_at', '>=', now()->subDays($days));
    });
  }

  #[Scope]
  public function unengaged(Builder $query, $days = 30)
  {
    return $query->where(function ($q) use ($days) {
      $q->whereNull('last_opened_at')
        ->orWhereDate('last_opened_at', '<', now()->subDays($days));
    });
  }

  // Additional attributes
  public function engagementScore(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->calculateEngagementScore()
    );
  }

  private function calculateEngagementScore()
  {
    if ($this->emails_received === 0) return 0;

    $openRate = $this->emails_opened / $this->emails_received;
    $clickRate = $this->emails_clicked / $this->emails_received;

    return round(($openRate * 0.4 + $clickRate * 0.6) * 100, 2);
  }

  public function averageOpenRate(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->emails_received > 0
        ? round(($this->emails_opened / $this->emails_received) * 100, 2)
        : 0
    );
  }

  public function averageClickRate(): Attribute
  {
    return Attribute::make(
      get: fn() => $this->emails_received > 0
        ? round(($this->emails_clicked / $this->emails_received) * 100, 2)
        : 0
    );
  }

  // Helper Methods
  public function unsubscribe(?string $reason = null): void
  {
    $this->update([
      'status' => SubscriberStatus::UNSUBSCRIBED,
      'unsubscribed_at' => now(),
      'unsubscribe_reason' => $reason
    ]);
  }

  public function fullName(): Attribute
  {
    return Attribute::make(
      get: fn() => trim("{$this->first_name} {$this->last_name}")
    );
  }

  public function getCustomField(string $key, $default = null)
  {
    return data_get($this->custom_fields, $key, $default);
  }

  // Helper methods
  public function updateEngagementMetrics(): void
  {
    $this->update([
      'emails_received' => $this->events()->where('type', 'sent')->count(),
      'emails_opened' => $this->events()->where('type', 'opened')->count(),
      'emails_clicked' => $this->events()->where('type', 'clicked')->count(),
    ]);
  }

  public function recordEngagement(string $type): void
  {
    $timeField = match ($type) {
      'opened' => 'last_opened_at',
      'clicked' => 'last_clicked_at',
      'sent' => 'last_emailed_at',
      default => null
    };

    if ($timeField) {
      $this->update([$timeField => now()]);
    }
  }

  public function addToList(MailingList $list): void
  {
    if (!$this->mailingLists->contains($list->id)) {
      $this->mailingLists()->attach($list->id, [
        'status' => SubscriberStatus::SUBSCRIBED->value,
        'subscribed_at' => now()
      ]);
    }
  }

  public function removeFromList(MailingList $list): void
  {
    $this->mailingLists()->detach($list->id);
  }

  public function resubscribe(): void
  {
    $this->update([
      'status' => SubscriberStatus::SUBSCRIBED,
      'subscribed_at' => now(),
      'unsubscribed_at' => null,
      'unsubscribe_reason' => null
    ]);
  }

  public function markAsUnsubscribed(string $reason = null): void
  {
    $this->update([
      'status' => SubscriberStatus::UNSUBSCRIBED,
      'unsubscribed_at' => now(),
      'unsubscribe_reason' => $reason,
      'metadata' => array_merge($this->metadata ?? [], [
        'unsubscribe_reason' => $reason,
        'unsubscribe_date' => now()->toDateTimeString()
      ])
    ]);
  }

  public function markAsBounced(string $reason = null): void
  {
    $this->update([
      'status' => SubscriberStatus::BOUNCED,
      'metadata' => array_merge($this->metadata ?? [], [
        'bounce_reason' => $reason,
        'bounce_date' => now()->toDateTimeString()
      ])
    ]);
  }

  public function markAsComplained(string $reason = null): void
  {
    $this->update([
      'status' => SubscriberStatus::COMPLAINED,
      'metadata' => array_merge($this->metadata ?? [], [
        'complaint_reason' => $reason,
        'complaint_date' => now()->toDateTimeString()
      ])
    ]);
  }
}
