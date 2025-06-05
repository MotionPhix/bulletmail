<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailQuota extends Model
{
  protected $fillable = [
    'user_id',
    'monthly_limit',
    'monthly_used',
    'daily_limit',
    'daily_used',
    'last_reset_at'
  ];

  protected $casts = [
    'monthly_limit' => 'integer',
    'monthly_used' => 'integer',
    'daily_limit' => 'integer',
    'daily_used' => 'integer',
    'last_reset_at' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function hasAvailableQuota(): bool
  {
    return $this->hasMonthlyQuota() && $this->hasDailyQuota();
  }

  public function hasMonthlyQuota(): bool
  {
    return $this->monthly_used < $this->monthly_limit;
  }

  public function hasDailyQuota(): bool
  {
    $this->resetDailyQuotaIfNeeded();
    return $this->daily_used < $this->daily_limit;
  }

  public function incrementUsage(int $amount = 1): void
  {
    $this->resetDailyQuotaIfNeeded();
    $this->increment('monthly_used', $amount);
    $this->increment('daily_used', $amount);
  }

  protected function resetDailyQuotaIfNeeded(): void
  {
    if (!$this->last_reset_at || $this->last_reset_at->isYesterday()) {
      $this->update([
        'daily_used' => 0,
        'last_reset_at' => now()
      ]);
    }
  }

  public function getRemainingMonthlyQuota(): int
  {
    return max(0, $this->monthly_limit - $this->monthly_used);
  }

  public function getRemainingDailyQuota(): int
  {
    $this->resetDailyQuotaIfNeeded();
    return max(0, $this->daily_limit - $this->daily_used);
  }
}
