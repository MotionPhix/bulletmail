<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Subscriber;
use App\Enums\CampaignStatus;
use App\Enums\EventType;
use App\Jobs\SendCampaignBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        'template_id' => $data['template_id'],
        'content' => $data['content'],
        'status' => isset($data['scheduled_at']) ? 'scheduled' : 'draft',
        'scheduled_at' => $data['scheduled_at'] ?? null
      ]);

      // Attach mailing lists
      $campaign->mailingLists()->attach($data['list_ids']);

      // Create initial stats record
      $campaign->stats()->create([
        'recipients_count' => $campaign->mailingLists()
          ->withCount('subscribers')
          ->get()
          ->sum('subscribers_count')
      ]);

      return $campaign;
    });
  }

  public function send(Campaign $campaign): void
  {
    if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
      throw new \Exception('Campaign cannot be sent');
    }

    $campaign->update(['status' => 'sending', 'started_at' => now()]);

    // Queue campaign sending in batches
    $campaign->mailingLists()
      ->with(['subscribers' => fn($q) => $q->select('subscribers.id', 'email')])
      ->chunk(100, function ($lists) use ($campaign) {
        foreach ($lists as $list) {
          SendCampaignBatch::dispatch($campaign, $list->subscribers);
        }
      });
  }
}
