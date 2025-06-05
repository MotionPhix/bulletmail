<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Models\TrackingEvent;
use App\Enums\EventType;
use Carbon\Carbon;

class EventTrackingService
{
  public function trackEvent(
    Campaign $campaign,
    Subscriber $subscriber,
    EventType $type,
    array $metadata = []
  ): TrackingEvent {
    $event = TrackingEvent::create([
      'team_id' => $campaign->team_id,
      'campaign_id' => $campaign->id,
      'subscriber_id' => $subscriber->id,
      'type' => $type,
      'email' => $subscriber->email,
      'metadata' => $metadata,
      'occurred_at' => Carbon::now()
    ]);

    // Update campaign stats
    $campaign->updateStats($type);

    // Update subscriber engagement
    $subscriber->updateEngagement($type);

    return $event;
  }

  public function getEventStats(Campaign $campaign): array
  {
    return [
      'sent' => $this->getEventCount($campaign, EventType::SENT),
      'delivered' => $this->getEventCount($campaign, EventType::DELIVERED),
      'opened' => $this->getEventCount($campaign, EventType::OPENED),
      'clicked' => $this->getEventCount($campaign, EventType::CLICKED),
      'bounced' => $this->getEventCount($campaign, EventType::BOUNCED),
      'complained' => $this->getEventCount($campaign, EventType::COMPLAINED),
      'unsubscribed' => $this->getEventCount($campaign, EventType::UNSUBSCRIBED),
    ];
  }

  private function getEventCount(Campaign $campaign, EventType $type): int
  {
    return TrackingEvent::forCampaign($campaign->id)
      ->ofType($type)
      ->count();
  }
}
