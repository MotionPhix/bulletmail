<?php

namespace App\Enums;

enum EmailTemplateType: string
{
  case DRAG_DROP = 'drag-drop';
  case HTML = 'html';
  case MARKDOWN = 'markdown';
  case PLAIN_TEXT = 'plain-text';

  public static function getLabels(): array
  {
    return collect(self::cases())->map(fn($type) => [
      'value' => $type->value,
      'label' => str($type->name)->title()->toString()
    ])->values()->all();
  }
}
