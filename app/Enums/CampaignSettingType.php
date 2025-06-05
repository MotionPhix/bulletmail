<?php

namespace App\Enums;

enum CampaignSettingType: string
{
  case TRACKING = 'tracking';
  case ANALYTICS = 'analytics';
  case SCHEDULING = 'scheduling';
  case SENDING = 'sending';

  public function getDefaultValue(): array
  {
    return match ($this) {
      self::TRACKING => [
        'open_tracking' => true,
        'click_tracking' => true,
        'unsubscribe_tracking' => true
      ],
      self::ANALYTICS => [
        'utm_source' => null,
        'utm_medium' => 'email',
        'utm_campaign' => null
      ],
      self::SCHEDULING => [
        'time_zone' => 'UTC',
        'batch_size' => 500,
        'batch_interval' => 300 // 5 minutes
      ],
      self::SENDING => [
        'priority' => 'normal',
        'throttle' => 1000, // per hour
        'retry_failed' => true,
        'max_retries' => 3
      ]
    };
  }
}
