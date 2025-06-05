<?php

namespace App\Traits;

use App\Models\Subscription;
use Carbon\Carbon;

trait HasSubscription
{
  public function onTrial(): bool
  {
    return $this->trial_ends_at && Carbon::now()->lt($this->trial_ends_at);
  }

  public function subscribed(): bool
  {
    return $this->activeSubscription() !== null;
  }

  public function canCreateCampaign(): bool
  {
    $subscription = $this->activeSubscription();
    if (!$subscription && !$this->onTrial()) {
      return false;
    }

    // Check campaign limit
    $campaignLimit = $subscription->plan->features['campaign_limit'] ?? 0;
    $currentCampaigns = $this->campaigns()->count();

    return $currentCampaigns < $campaignLimit;
  }

  public function canSendEmails(): bool
  {
    $subscription = $this->activeSubscription();
    if (!$subscription && !$this->onTrial()) {
      return false;
    }

    // Check monthly email limit
    $emailLimit = $subscription->plan->features['email_limit'] ?? 0;
    $currentMonth = Carbon::now()->startOfMonth();
    $emailsSent = $this->campaigns()
      ->where('sent_at', '>=', $currentMonth)
      ->sum('emails_sent');

    return $emailsSent < $emailLimit;
  }

  public function canScheduleCampaigns(): bool
  {
    $subscription = $this->activeSubscription();
    if (!$subscription && !$this->onTrial()) {
      return false;
    }

    return $subscription->plan->features['can_schedule_campaigns'] ?? false;
  }
}
