<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, Factories\HasFactory};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignStats extends Model
{
  use HasFactory;

  protected $fillable = [
    'campaign_id',
    'recipients_count',
    'delivered_count',
    'opened_count',
    'clicked_count',
    'bounced_count',
    'complained_count',
    'unsubscribed_count'
  ];

  protected $casts = [
    'recipients_count' => 'integer',
    'delivered_count' => 'integer',
    'opened_count' => 'integer',
    'clicked_count' => 'integer',
    'bounced_count' => 'integer',
    'complained_count' => 'integer',
    'unsubscribed_count' => 'integer'
  ];

  public function campaign(): BelongsTo
  {
    return $this->belongsTo(Campaign::class);
  }

  public function getDeliveryRateAttribute(): float
  {
    if (!$this->recipients_count) {
      return 0;
    }
    return round(($this->delivered_count / $this->recipients_count) * 100, 2);
  }

  public function getOpenRateAttribute(): float
  {
    if (!$this->delivered_count) {
      return 0;
    }
    return round(($this->opened_count / $this->delivered_count) * 100, 2);
  }

  public function getClickRateAttribute(): float
  {
    if (!$this->delivered_count) {
      return 0;
    }
    return round(($this->clicked_count / $this->delivered_count) * 100, 2);
  }

  public function getBounceRateAttribute(): float
  {
    if (!$this->recipients_count) {
      return 0;
    }
    return round(($this->bounced_count / $this->recipients_count) * 100, 2);
  }

  public function getUnsubscribeRateAttribute(): float
  {
    if (!$this->delivered_count) {
      return 0;
    }
    return round(($this->unsubscribed_count / $this->delivered_count) * 100, 2);
  }

  public function incrementStat(string $stat, int $amount = 1): void
  {
    if (in_array($stat, $this->fillable)) {
      $this->increment($stat, $amount);
    }
  }
}
