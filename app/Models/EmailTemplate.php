<?php

namespace App\Models;

use App\Traits\{HasTeamScope, HasUuid};
use App\Enums\{TemplateCategory, TemplateType};
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class EmailTemplate extends Model
{
  use HasFactory, HasUuid, HasTeamScope, SoftDeletes;

  protected $fillable = [
    'team_id',
    'user_id',
    'name',
    'description',
    'subject',
    'content',
    'preview_text',
    'category',
    'type',
    'design',
    'variables',
    'tags',
    'sendgrid_template_id',
    'last_synced_at'
  ];

  protected $casts = [
    'category' => TemplateCategory::class,
    'type' => TemplateType::class,
    'design' => 'array',
    'variables' => 'array',
    'tags' => 'array',
    'last_synced_at' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function campaigns(): HasMany
  {
    return $this->hasMany(Campaign::class);
  }

  public function duplicate(): self
  {
    $clone = $this->replicate(['sendgrid_template_id', 'last_synced_at']);
    $clone->name = "{$this->name} (Copy)";
    $clone->save();

    return $clone;
  }

  public function isInUse(): bool
  {
    return $this->campaigns()->exists();
  }

  public function getDesignProperty($key, $default = null)
  {
    return data_get($this->design, $key, $default);
  }

  public function setDesignProperty($key, $value): void
  {
    $design = $this->design ?? [];
    data_set($design, $key, $value);
    $this->design = $design;
  }

  public function syncWithSendGrid(): bool
  {
    try {
      // SendGrid sync logic will be implemented in the service
      return true;
    } catch (\Exception $e) {
      report($e);
      return false;
    }
  }

  protected static function booted()
  {
    static::created(function (EmailTemplate $template) {
      if ($template->type === TemplateType::HTML) {
        $template->syncWithSendGrid();
      }
    });

    static::updated(function (EmailTemplate $template) {
      if ($template->type === TemplateType::HTML && $template->isDirty('content')) {
        $template->syncWithSendGrid();
      }
    });
  }

  public function scopeByType($query, TemplateType $type)
  {
    return $query->where('type', $type);
  }

  public function scopeByCategory($query, TemplateCategory $category)
  {
    return $query->where('category', $category);
  }
}
