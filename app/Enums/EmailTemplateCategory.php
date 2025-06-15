<?php

namespace App\Enums;

enum EmailTemplateCategory: string
{
  case MARKETING = 'marketing';
  case TRANSACTIONAL = 'transactional';
  case NEWSLETTER = 'newsletter';
  case ANNOUNCEMENT = 'announcement';
  case ONBOARDING = 'onboarding';
  case NOTIFICATION = 'notification';

  public static function getLabels(): array
  {
    return collect(self::cases())->map(fn($category) => [
      'value' => $category->value,
      'label' => str($category->name)->title()->toString()
    ])->values()->all();
  }
}
