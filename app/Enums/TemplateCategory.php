<?php

namespace App\Enums;

enum TemplateCategory: string
{
  case MARKETING = 'marketing';
  case TRANSACTIONAL = 'transactional';
  case NEWSLETTER = 'newsletter';
  case ANNOUNCEMENT = 'announcement';
  case ONBOARDING = 'onboarding';
  case NOTIFICATION = 'notification';
}
