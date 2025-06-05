<?php

namespace App\Models;

use App\Enums\ListType;
use App\Traits\{HasTeamScope, HasUuid};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class MailingList extends Model
{
  use HasFactory, HasUuid, HasTeamScope, SoftDeletes;

  protected $fillable = [
    'team_id',
    'user_id',
    'name',
    'description',
    'type',
    'settings',
    'segment_rules',
    'double_opt_in',
    'welcome_email_id',
    'subscriber_count',
    'last_synced_at'
  ];

  protected $casts = [
    'type' => ListType::class,
    'settings' => 'array',
    'segment_rules' => 'array',
    'double_opt_in' => 'boolean',
    'subscriber_count' => 'integer',
    'last_synced_at' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function subscribers(): BelongsToMany
  {
    return $this->belongsToMany(Subscriber::class)
      ->withPivot(['status', 'subscribed_at', 'unsubscribed_at', 'metadata'])
      ->withTimestamps();
  }

  public function activeSubscribers(): BelongsToMany
  {
    return $this->subscribers()
      ->wherePivot('status', 'subscribed');
  }

  public function campaigns(): HasMany
  {
    return $this->hasMany(Campaign::class);
  }

  public function scopeStandard($query)
  {
    return $query->where('type', ListType::STANDARD);
  }

  public function scopeSystem($query)
  {
    return $query->where('type', ListType::SYSTEM);
  }

  public function scopeSegment($query)
  {
    return $query->where('type', ListType::SEGMENT);
  }

  public function updateSubscriberCount(): void
  {
    $this->subscriber_count = $this->activeSubscribers()->count();
    $this->save();
  }

  public function addSubscribers(array $subscribers): void
  {
    $now = now();
    $attachData = collect($subscribers)->mapWithKeys(function ($email) use ($now) {
      return [$email => [
        'status' => 'subscribed',
        'subscribed_at' => $now,
        'metadata' => ['source' => 'bulk_import']
      ]];
    })->all();

    $this->subscribers()->attach($attachData);
    $this->updateSubscriberCount();
  }

  public function removeSubscribers(array $subscribers): void
  {
    $this->subscribers()->detach($subscribers);
    $this->updateSubscriberCount();
  }
}
