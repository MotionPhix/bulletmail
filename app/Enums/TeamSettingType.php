<?php

namespace App\Enums;

enum TeamSettingType: string
{
  case EMAIL = 'email';
  case BRANDING = 'branding';
  case QUOTA = 'quota';
  case NOTIFICATIONS = 'notifications';
  case INTEGRATION = 'integration';

  public function getDefaultValue(): array
  {
    return match ($this) {
      self::EMAIL => [
        'from_name' => '',
        'from_email' => '',
        'reply_to' => '',
        'footer_text' => '',
        'unsubscribe_link' => true,
        'tracking_enabled' => true
      ],
      self::BRANDING => [
        'company_name' => '',
        'logo_url' => '',
        'primary_color' => '#4F46E5',
        'accent_color' => '#818CF8',
        'custom_css' => '',
        'email_header' => '',
        'email_footer' => ''
      ],
      self::QUOTA => [
        'monthly_limit' => 10000,
        'daily_limit' => 1000,
        'monthly_used' => 0,
        'daily_used' => 0,
        'last_reset' => null
      ],
      self::NOTIFICATIONS => [
        'campaign_sent' => true,
        'campaign_failed' => true,
        'list_growth' => true,
        'quota_warning' => true
      ],
      self::INTEGRATION => [
        'sendgrid' => [
          'api_key' => '',
          'enabled' => false
        ],
        'smtp' => [
          'host' => '',
          'port' => '',
          'username' => '',
          'password' => '',
          'encryption' => 'tls',
          'enabled' => false
        ]
      ]
    };
  }
}
