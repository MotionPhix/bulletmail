<?php

namespace App\Models;

use App\Traits\{HasTeamScope, HasUuid};
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};

class Campaign extends Model
{
  use HasFactory, HasUuid, HasTeamScope, SoftDeletes;

  protected $fillable = [
    'team_id',
    'user_id',
    'template_id',
    'name',
    'description',
    'subject',
    'content',
    'preview_text',
    'from_name',
    'from_email',
    'reply_to',
    'status',
    'scheduled_at',
    'started_at',
    'completed_at',
    'recipient_lists',
    'recipient_segments',
    'total_recipients',
    'sendgrid_campaign_id',
    'sendgrid_settings'
  ];

  protected $casts = [
    'scheduled_at' => 'datetime',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
    'recipient_lists' => 'array',
    'recipient_segments' => 'array',
    'sendgrid_settings' => 'array'
  ];

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function template(): BelongsTo
  {
    return $this->belongsTo(EmailTemplate::class);
  }

  public function stats(): HasOne
  {
    return $this->hasOne(CampaignStats::class);
  }

  public function events(): HasMany
  {
    return $this->hasMany(CampaignEvent::class);
  }

  public function scopeScheduled($query)
  {
    return $query->where('status', CampaignStatus::SCHEDULED);
  }

  public function scopePending($query)
  {
    return $query->whereIn('status', [
      CampaignStatus::DRAFT,
      CampaignStatus::SCHEDULED
    ]);
  }

  public function scopeCompleted($query)
  {
    return $query->where('status', CampaignStatus::COMPLETED);
  }

  public function isScheduled(): bool
  {
    return $this->status === CampaignStatus::SCHEDULED;
  }

  public function canBeSent(): bool
  {
    return in_array($this->status, [
      CampaignStatus::DRAFT,
      CampaignStatus::SCHEDULED
    ]);
  }
}
