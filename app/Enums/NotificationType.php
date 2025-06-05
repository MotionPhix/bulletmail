<?php

namespace App\Enums;

enum NotificationType: string
{
  case CAMPAIGN_SENT = 'campaign_sent';
  case CAMPAIGN_FAILED = 'campaign_failed';
  case NEW_SUBSCRIBER = 'new_subscriber';
  case QUOTA_WARNING = 'quota_warning';
  case TEMPLATE_CREATED = 'template_created';
  case USER_REGISTERED = 'user_registered';

  public function getIcon(): string
  {
    return match ($this) {
      self::CAMPAIGN_SENT => 'check-circle',
      self::CAMPAIGN_FAILED => 'x-circle',
      self::NEW_SUBSCRIBER => 'user-plus',
      self::QUOTA_WARNING => 'alert-triangle',
      self::TEMPLATE_CREATED => 'file-plus',
      self::USER_REGISTERED => 'user'
    };
  }

  public function getColor(): string
  {
    return match ($this) {
      self::CAMPAIGN_SENT => 'success',
      self::CAMPAIGN_FAILED => 'danger',
      self::NEW_SUBSCRIBER => 'info',
      self::QUOTA_WARNING => 'warning',
      self::TEMPLATE_CREATED => 'primary',
      self::USER_REGISTERED => 'success'
    };
  }
}
