<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class CampaignEvent extends Model
{
  use HasFactory;

  protected $fillable = [
    'campaign_id',
    'subscriber_id',
    'type',
    'metadata'
  ];

  protected $casts = [
    'type' => EventType::class,
    'metadata' => 'array'
  ];

  public function campaign(): BelongsTo
  {
    return $this->belongsTo(Campaign::class);
  }

  public function subscriber(): BelongsTo
  {
    return $this->belongsTo(Subscriber::class);
  }

  public function scopeOfType($query, EventType $type)
  {
    return $query->where('type', $type);
  }

  public function scopeForSubscriber($query, $subscriberId)
  {
    return $query->where('subscriber_id', $subscriberId);
  }

  public function scopeForCampaign($query, $campaignId)
  {
    return $query->where('campaign_id', $campaignId);
  }

  public function scopeRecent($query, int $days = 30)
  {
    return $query->where('created_at', '>=', now()->subDays($days));
  }
}
