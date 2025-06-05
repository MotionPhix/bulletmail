<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use App\Traits\{HasTeamScope, HasUuid};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
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

  // Helper Methods
  public function unsubscribe(?string $reason = null): void
  {
    $this->update([
      'status' => SubscriberStatus::UNSUBSCRIBED,
      'unsubscribed_at' => now(),
      'unsubscribe_reason' => $reason
    ]);
  }

  public function getFullNameAttribute(): string
  {
    return trim("{$this->first_name} {$this->last_name}");
  }

  public function getCustomField(string $key, $default = null)
  {
    return data_get($this->custom_fields, $key, $default);
  }
}
