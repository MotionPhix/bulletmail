<?php

namespace App\Enums;

enum CampaignStatus: string
{
  case DRAFT = 'draft';
  case SCHEDULED = 'scheduled';
  case SENDING = 'sending';
  case SENT = 'sent';
  case PAUSED = 'paused';
  case FAILED = 'failed';
  case CANCELLED = 'cancelled';

  public function getDescription(): string
  {
    return match ($this) {
      self::DRAFT => 'Campaign is in draft mode',
      self::SCHEDULED => 'Campaign is scheduled to send',
      self::SENDING => 'Campaign is currently sending',
      self::SENT => 'Campaign has been sent',
      self::PAUSED => 'Campaign is paused',
      self::FAILED => 'Campaign failed to send',
      self::CANCELLED => 'Campaign was cancelled'
    };
  }

  public function canEdit(): bool
  {
    return in_array($this, [self::DRAFT, self::SCHEDULED, self::PAUSED]);
  }

  public function canSend(): bool
  {
    return in_array($this, [self::DRAFT, self::SCHEDULED, self::PAUSED]);
  }
}
