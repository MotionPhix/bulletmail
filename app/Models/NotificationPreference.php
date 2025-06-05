<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationPreference extends Model
{
  protected $fillable = [
    'preferenceable_type',
    'preferenceable_id',
    'type',
    'channels',
    'enabled'
  ];

  protected $casts = [
    'type' => NotificationType::class,
    'channels' => 'array',
    'enabled' => 'boolean'
  ];

  protected $attributes = [
    'channels' => '[]',
    'enabled' => true
  ];

  public function notifiable(): MorphTo
  {
    return $this->morphTo();
  }

  public function isEnabledFor(string $channel): bool
  {
    return in_array($channel, $this->channels ?? []);
  }

  public function enableChannel(string $channel): void
  {
    if (!$this->isEnabledFor($channel)) {
      $this->channels = array_merge($this->channels ?? [], [$channel]);
      $this->save();
    }
  }

  public function disableChannel(string $channel): void
  {
    if ($this->isEnabledFor($channel)) {
      $this->channels = array_diff($this->channels ?? [], [$channel]);
      $this->save();
    }
  }

  public function getSetting(string $key, $default = null)
  {
    return data_get($this->settings, $key, $default);
  }

  public function setSetting(string $key, $value): void
  {
    $settings = $this->settings ?? [];
    data_set($settings, $key, $value);
    $this->settings = $settings;
    $this->save();
  }

  public function scopeForType($query, NotificationType $type)
  {
    return $query->where('type', $type);
  }

  public function scopeForChannel($query, string $channel)
  {
    return $query->whereJsonContains('channels', $channel);
  }
}
