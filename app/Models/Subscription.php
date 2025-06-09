<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
  use HasFactory, HasUuid, SoftDeletes;

  const STATUS_ACTIVE = 'active';
  const STATUS_TRIAL = 'trial';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_EXPIRED = 'expired';
  const STATUS_PENDING = 'pending';

  protected $fillable = [
    'organization_id',
    'user_id',
    'plan_id',
    'status',
    'starts_at',
    'ends_at',
    'trial_ends_at',
    'cancelled_at',
    'last_payment_at',
    'payment_method',
    'payment_reference'
  ];

  protected $casts = [
    'starts_at' => 'datetime',
    'ends_at' => 'datetime',
    'trial_ends_at' => 'datetime',
    'cancelled_at' => 'datetime',
    'last_payment_at' => 'datetime',
    'metadata' => 'array',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function plan(): BelongsTo
  {
    return $this->belongsTo(Plan::class);
  }

  public function organization(): BelongsTo
  {
    return $this->belongsTo(Organization::class);
  }

  public function isActive(): bool
  {
    return $this->status === self::STATUS_ACTIVE;
  }

  public function isOnTrial(): bool
  {
    return $this->status === self::STATUS_TRIAL
      && $this->trial_ends_at
      && !$this->trial_ends_at->isPast();
  }

  public function hasFeature(string $feature): bool
  {
    return (bool) $this->plan->getFeature($feature);
  }

  public function getFeatureValue(string $feature, $default = null)
  {
    return $this->plan->getFeature($feature, $default);
  }

  public function calculateTrialEndsAt(): Carbon
  {
    return $this->starts_at->addDays($this->plan->trial_days);
  }

  public function isCancelled(): bool
  {
    return $this->status === self::STATUS_CANCELLED;
  }

  public function isExpired(): bool
  {
    return $this->status === self::STATUS_EXPIRED ||
      ($this->ends_at && $this->ends_at->isPast());
  }
}
