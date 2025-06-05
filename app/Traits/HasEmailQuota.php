<?php

namespace App\Traits;

use App\Models\EmailQuota;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait HasEmailQuota
{
  public function emailQuota(): HasOne
  {
    return $this->hasOne(EmailQuota::class);
  }

  public function emailQuotaRemaining(): Attribute
  {
    return Attribute::get(function() {
      $quotaUsed = $this->trackingEvents()
        ->where('type', 'sent')
        ->where('created_at', '>=', now()->startOfMonth())
        ->count();

      return max(0, $this->settings->email_quota - $quotaUsed);
    });
  }

  public function emailQuotaUsed(): Attribute
  {
    return Attribute::get(function() {
      return $this->trackingEvents()
        ->where('type', 'sent')
        ->where('created_at', '>=', now()->startOfMonth())
        ->count();
    });
  }

  public function initializeEmailQuota(): void
  {
    $this->emailQuota()->create([
      'monthly_limit' => config('mail.quotas.monthly_default', 1000),
      'monthly_used' => 0,
      'daily_limit' => config('mail.quotas.daily_default', 100),
      'daily_used' => 0,
      'last_reset_at' => now(),
    ]);
  }

  public function incrementEmailQuota(int $amount = 1): bool
  {
    if (!$this->canSendEmails($amount)) {
      return false;
    }

    $this->emailQuota->increment('daily_used', $amount);
    $this->emailQuota->increment('monthly_used', $amount);

    return true;
  }

  public function canSendEmails(int $amount = 1): bool
  {
    return $this->emailQuota &&
      $this->emailQuota->hasAvailableQuota() &&
      ($this->emailQuota->daily_used + $amount <= $this->emailQuota->daily_limit) &&
      ($this->emailQuota->monthly_used + $amount <= $this->emailQuota->monthly_limit);
  }

  public function getQuotaUsagePercentage(): array
  {
    if (!$this->emailQuota) {
      return [
        'daily' => 0,
        'monthly' => 0
      ];
    }

    return [
      'daily' => ($this->emailQuota->daily_used / $this->emailQuota->daily_limit) * 100,
      'monthly' => ($this->emailQuota->monthly_used / $this->emailQuota->monthly_limit) * 100
    ];
  }

  public function hasEmailQuotaRemaining(): bool
  {
    return $this->email_quota_remaining > 0;
  }

  public function incrementEmailQuotaUsage(int $count = 1): void
  {
    // Create sent events
    for ($i = 0; $i < $count; $i++) {
      $this->trackingEvents()->create([
        'type' => 'sent',
        'occurred_at' => now(),
      ]);
    }
  }
}
