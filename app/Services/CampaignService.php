<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\Subscriber;
use App\Enums\CampaignStatus;
use App\Enums\EventType;
use App\Jobs\SendCampaignBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignService
{
  public function __construct(
    protected EventTrackingService $eventTracker
  ) {}

  public function create(array $data): Campaign
  {
    return DB::transaction(function () use ($data) {
      $campaign = Campaign::create([
        'team_id' => auth()->user()->current_team_id,
        'user_id' => auth()->id(),
        'name' => $data['name'],
        'subject' => $data['subject'],
        'preview_text' => $data['preview_text'] ?? null,
        'template_id' => $data['template_id'],
        'content' => $data['content']['html'],
        'design' => $data['content']['design'],
        'from_name' => $data['from_name'],
        'from_email' => $data['from_email'],
        'merge_tags' => $data['merge_tags'] ?? [],
        'reply_to' => $data['reply_to'] ?? $data['from_email'],
        'status' => isset($data['scheduled_at'])
          ? CampaignStatus::SCHEDULED
          : CampaignStatus::DRAFT,
        'scheduled_at' => $data['scheduled_at'] ?? null
      ]);

      // Attach mailing lists
      if (!empty($data['mailing_list_ids'])) {
        $campaign->mailingLists()->attach($data['mailing_list_ids']);
      }

      // Create initial stats record
      $totalRecipients = $campaign->mailingLists()
        ->withCount('subscribers')
        ->get()
        ->sum('subscribers_count');

      $campaign->stats()->create([
        'recipients_count' => $totalRecipients,
      ]);

      // Update campaign total recipients
      $campaign->update([
        'total_recipients' => $totalRecipients
      ]);

      Log::info('Campaign created', [
        'campaign_id' => $campaign->id,
        'team_id' => $campaign->team_id,
        'recipients' => $totalRecipients
      ]);

      return $campaign;
    });
  }

  public function update(Campaign $campaign, array $data): Campaign
  {
    // If template changed, copy new template data
    if (isset($data['template_id']) && !$campaign->usesTemplate($data['template_id'])) {
      $campaign->template_id = $data['template_id'];
      $campaign->copyFromTemplate(EmailTemplate::find($data['template_id']));
    }

    // Update other campaign-specific data
    $campaign->update($data);

    return $campaign;
  }

  public function send(Campaign $campaign): void
  {
    $errors = $this->validateBeforeSend($campaign);

    if (!empty($errors)) {
      throw new \Exception(implode(', ', $errors));
    }

    $campaign->update([
      'status' => CampaignStatus::SENDING,
      'started_at' => now()
    ]);

    // Queue campaign sending in batches
    $campaign->mailingLists()
      ->with(['subscribers' => fn($q) => $q->select('subscribers.id', 'email')])
      ->chunk(100, function ($lists) use ($campaign) {
        foreach ($lists as $list) {
          SendCampaignBatch::dispatch($campaign, $list->subscribers);
        }
      });
  }

  public function validateBeforeSend(Campaign $campaign): array
  {
    $errors = [];

    if (empty($campaign->content)) {
      $errors[] = 'Campaign content is empty';
    }

    if ($campaign->total_recipients === 0) {
      $errors[] = 'No recipients selected';
    }

    if (!$campaign->from_email || !filter_var($campaign->from_email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid sender email address';
    }

    if ($campaign->reply_to && !filter_var($campaign->reply_to, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Invalid reply-to email address';
    }

    return $errors;
  }
}
