<?php

namespace App\Enums;

enum ListType: string
{
  case STANDARD = 'standard';
  case SYSTEM = 'system';
  case SEGMENT = 'segment';
  case AUTOMATED = 'automated';

  public function getDescription(): string
  {
    return match ($this) {
      self::STANDARD => 'Manual subscriber list',
      self::SYSTEM => 'System-managed list',
      self::SEGMENT => 'Dynamic segment list',
      self::AUTOMATED => 'Automation-managed list'
    };
  }
}
