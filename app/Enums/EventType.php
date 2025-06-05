<?php

namespace App\Enums;

enum EventType: string
{
  case SENT = 'sent';
  case DELIVERED = 'delivered';
  case OPENED = 'opened';
  case CLICKED = 'clicked';
  case BOUNCED = 'bounced';
  case COMPLAINED = 'complained';
  case UNSUBSCRIBED = 'unsubscribed';

  public function getDescription(): string
  {
    return match ($this) {
      self::SENT => 'Email was sent',
      self::DELIVERED => 'Email was delivered',
      self::OPENED => 'Email was opened',
      self::CLICKED => 'Link was clicked',
      self::BOUNCED => 'Email bounced',
      self::COMPLAINED => 'Marked as spam',
      self::UNSUBSCRIBED => 'Unsubscribed from emails'
    };
  }

  public function isNegative(): bool
  {
    return in_array($this, [
      self::BOUNCED,
      self::COMPLAINED,
      self::UNSUBSCRIBED
    ]);
  }
}
