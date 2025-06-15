<?php

namespace App\Enums;

enum EmailTemplateStatus: string
{
  case DRAFT = 'draft';
  case PUBLISHED = 'published';
  case ARCHIVED = 'archived';
  case DELETED = 'deleted';

  public function getDescription(): string
  {
    return match ($this) {
      self::DRAFT => 'Template is in draft mode',
      self::PUBLISHED => 'Template is published and active',
      self::ARCHIVED => 'Template is archived and not active',
      self::DELETED => 'Template has been deleted',
    };
  }

  public static function getLabels(): array
  {
    return collect(self::cases())->map(fn($status) => [
      'value' => $status->value,
      'label' => str($status->name)->title()->toString()
    ])->values()->all();
  }

  public function canPublish(): bool
  {
    return $this === self::DRAFT;
  }

  public function canEdit(): bool
  {
    return $this === self::DRAFT || $this === self::PUBLISHED;
  }
}
