<?php

namespace App\Enums;

enum OnboardingStep: int
{
  case WELCOME = 1;
  case TEAM_SETUP = 2;
  case EMAIL_SETTINGS = 3;
  case BRANDING = 4;
  case IMPORT_CONTACTS = 5;
  case CUSTOMIZE = 6;
  case COMPLETE = 7;

  public function getTitle(): string
  {
    return match ($this) {
      self::WELCOME => 'Welcome',
      self::TEAM_SETUP => 'Team Setup',
      self::EMAIL_SETTINGS => 'Email Settings',
      self::BRANDING => 'Brand Settings',
      self::IMPORT_CONTACTS => 'Import Contacts',
      self::CUSTOMIZE => 'Customize',
      self::COMPLETE => 'Onboarding Complete',
    };
  }

  public function canSkip(): bool
  {
    return match ($this) {
      self::WELCOME, self::TEAM_SETUP => false, self::COMPLETE => false,
      default => true
    };
  }
}
