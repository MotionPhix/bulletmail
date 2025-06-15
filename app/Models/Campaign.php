<?php

namespace App\Models;

use App\Traits\{HasTeamScope, HasUuid};
use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\{Attributes\Scope, Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne};

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
    'design',
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
    'mailing_list_ids',
    'sendgrid_campaign_id',
    'merge_tags',
  ];

  protected $casts = [
    'scheduled_at' => 'datetime',
    'started_at' => 'datetime',
    'completed_at' => 'datetime',
    'recipient_lists' => 'array',
    'recipient_segments' => 'array',
    'mailing_list_ids' => 'array',
    'design' => 'array',
    'merge_tags' => 'array'
  ];

  protected $with = ['template', 'mailingLists'];

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
    return $this->belongsTo(EmailTemplate::class, 'template_id');
  }

  public function stats(): HasOne
  {
    return $this->hasOne(CampaignStats::class);
  }

  public function events(): HasMany
  {
    return $this->hasMany(CampaignEvent::class);
  }

  public function mailingLists(): BelongsToMany
  {
    return $this->belongsToMany(MailingList::class, 'campaign_lists')
      ->withTimestamps();
  }

  public function activeMailingLists(): BelongsToMany
  {
    return $this->mailingLists()
      ->wherePivot('status', 'active');
  }

  #[Scope]
  public function scheduled(Builder $query): Builder
  {
    return $query->where('status', CampaignStatus::SCHEDULED);
  }

  #[Scope]
  public function pending(Builder $query): Builder
  {
    return $query->whereIn('status', [
      CampaignStatus::DRAFT,
      CampaignStatus::SCHEDULED
    ]);
  }

  #[Scope]
  public function completed(Builder $query): Builder
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

  // Get preview data with merge tags replaced
  public function getPreviewContent(?Subscriber $subscriber = null): string
  {
    $content = $this->content;
    $organization = $this->team->organization;

    // Add header/footer if they exist
    if ($organization->email_header) {
      $content = $organization->email_header . $content;
    }
    if ($organization->email_footer) {
      $content .= $organization->email_footer;
    }

    // Replace merge tags
    return $this->replaceMergeTags($content, $subscriber ?? $this->getRandomSubscriber());
  }

  protected function replaceMergeTags(string $content, Subscriber $subscriber): string
  {
    // Get campaign-specific merge tags
    $mergeTags = $this->merge_tags ?? [];

    // Default tags
    $defaultTags = [
      'subscriber.first_name' => $subscriber->first_name ?? '',
      'subscriber.last_name' => $subscriber->last_name ?? '',
      'subscriber.email' => $subscriber->email,
      'subscriber.unsubscribe_link' => route('subscriptions.unsubscribe', [
        'subscriber' => $subscriber->uuid,
        'campaign' => $this->uuid
      ]),
      'campaign.subject' => $this->subject,
      'campaign.preview_text' => $this->preview_text,
      'sender.name' => $this->from_name,
      'sender.email' => $this->from_email,
      'team.name' => $this->team->name,
    ];

    // Combine default tags with custom merge tags
    $allTags = array_merge(
      $defaultTags,
      collect($mergeTags)->mapWithKeys(fn ($tag) => [
        $tag['tag'] => $tag['default'] ?? ''
      ])->toArray()
    );

    // Replace all merge tags in content
    return collect($allTags)->reduce(function ($content, $value, $key) {
      return str_replace(
        ['{{'.$key.'}}', '{{ '.$key.' }}'],
        $value,
        $content
      );
    }, $content);
  }

  protected function getRandomSubscriber(): ?Subscriber
  {
    return $this->mailingLists()
      ->with('subscribers')
      ->get()
      ->flatMap->subscribers
      ->random();
  }

  public function copyFromTemplate(EmailTemplate $template): void
  {
    $this->content = $template->content;
    $this->design = $template->design;
    $this->merge_tags = $template->merge_tags;
    $this->subject = $template->subject;
    $this->preview_text = $template->preview_text;

    $this->save();
  }

  #[Scope]
  public function usingTemplate(Builder $query, int $templateId): Builder
  {
    return $query->where('template_id', $templateId);
  }

  public function usesTemplate(int $templateId): bool
  {
    return $this->template_id === $templateId;
  }
}
