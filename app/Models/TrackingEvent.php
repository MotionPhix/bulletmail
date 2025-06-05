<?php

namespace App\Models;

use App\Enums\EventType;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TrackingEvent extends Model
{
  use HasUuid;

  protected $fillable = [
    'uuid',
    'event_type',
    'email',
    'url',
    'ip_address',
    'user_agent',
    'metadata',
    'trackable_type',
    'trackable_id'
  ];

  protected $casts = [
    'event_type' => EventType::class,
    'metadata' => 'array'
  ];

  public function trackable(): MorphTo
  {
    return $this->morphTo();
  }

  public function isNegativeEvent(): bool
  {
    return $this->event_type->isNegative();
  }

  public static function track(
    EventType $eventType,
    string $email,
    $trackable,
    array $metadata = []
  ): self {
    return static::create([
      'event_type' => $eventType,
      'email' => $email,
      'trackable_type' => get_class($trackable),
      'trackable_id' => $trackable->id,
      'ip_address' => request()->ip(),
      'user_agent' => request()->userAgent(),
      'metadata' => array_merge($metadata, [
        'timestamp' => now()->timestamp,
        'referrer' => request()->header('referer')
      ])
    ]);
  }

  public function scopeOfType($query, EventType $type)
  {
    return $query->where('event_type', $type);
  }

  public function scopeForEmail($query, string $email)
  {
    return $query->where('email', $email);
  }
}
