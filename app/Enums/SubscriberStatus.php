<?php

namespace App\Enums;

enum SubscriberStatus: string
{
  case SUBSCRIBED = 'subscribed';
  case UNSUBSCRIBED = 'unsubscribed';
  case BOUNCED = 'bounced';
  case COMPLAINED = 'complained';
  case PENDING = 'pending';
  case CLEANED = 'cleaned';

  public function getDescription(): string
  {
    return match ($this) {
      self::SUBSCRIBED => 'Active subscriber',
      self::UNSUBSCRIBED => 'Manually unsubscribed',
      self::BOUNCED => 'Email bounced',
      self::COMPLAINED => 'Marked as spam',
      self::PENDING => 'Pending confirmation',
      self::CLEANED => 'Invalid or inactive email'
    };
  }

  public function canReceiveEmails(): bool
  {
    return $this === self::SUBSCRIBED;
  }
}
