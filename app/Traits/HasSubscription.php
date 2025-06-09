<?php

namespace App\Traits;

use App\Models\{Plan, Subscription};
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

trait HasSubscription
{
  public function subscription(): HasOne
  {
    return $this->hasOne(Subscription::class)->latest();
  }

  public function subscriptions()
  {
    return $this->hasMany(Subscription::class);
  }

  public function activeSubscription()
  {
    return $this->subscription()->where('status', 'active')->first();
  }

  public function onTrial(): bool
  {
    return $this->subscription &&
      $this->subscription->trial_ends_at &&
      Carbon::now()->lt($this->subscription->trial_ends_at);
  }

  public function subscribed(): bool
  {
    return $this->activeSubscription() !== null;
  }

  public function hasFeature(string $feature): bool
  {
    $subscription = $this->activeSubscription();
    if (!$subscription && !$this->onTrial()) {
      return false;
    }

    return $subscription->plan->hasFeature($feature);
  }

  public function getFeatureValue(string $feature, $default = null)
  {
    $subscription = $this->activeSubscription();
    if (!$subscription && !$this->onTrial()) {
      return $default;
    }

    return $subscription->plan->getFeature($feature, $default);
  }
}
