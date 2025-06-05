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
  private EventTrackingService $eventTracker;

  public function __construct(EventTrackingService $eventTracker)
  {
    $this->eventTracker = $eventTracker;
  }

  public function create(array $data): Campaign
  {
    return DB::transaction(function () use ($data) {
      $campaign = Campaign::create([
        'user_id' => auth()->user()->id,
        'team_id' => auth()->user()->currentTeam->id,
        'name' => $data['name'],
        'subject' => $data['subject'],
        'from_name' => $data['from_name'],
        'from_email' => $data['from_email'],
        'reply_to' => $data['reply_to'],
        'content' => $data['content'] ?? '',
        'template_id' => $data['template_id'],
        'scheduled_at' => $data['scheduled_at'] ?? null,
        'recipients' => $data['recipients'],
        'settings' => $data['settings'] ?? [],
        'current_step' => $data['current_step'] ?? 1,
        'status' => 'draft'
      ]);

      return $campaign;
    });
  }

  public function update(Campaign $campaign, array $data): Campaign
  {
    if (!in_array($campaign->status, ['draft', 'scheduled'])) {
      throw new \Exception('Only draft or scheduled campaigns can be updated.');
    }

    return DB::transaction(function () use ($campaign, $data) {
      $campaign->update([
        'name' => $data['name'],
        'subject' => $data['subject'],
        'from_name' => $data['from_name'],
        'from_email' => $data['from_email'],
        'reply_to' => $data['reply_to'],
        'content' => $data['content'] ?? '',
        'template_id' => $data['template_id'],
        'scheduled_at' => $data['scheduled_at'] ?? null,
        'recipients' => $data['recipients'],
        'settings' => array_merge($campaign->settings ?? [], $data['settings'] ?? []),
        'current_step' => $data['current_step'] ?? $campaign->current_step,
      ]);

      return $campaign->fresh(['template']);
    });
  }

  public function schedule(Campaign $campaign, Carbon $scheduledAt): void
  {
    $campaign->update([
      'status' => CampaignStatus::SCHEDULED,
      'scheduled_at' => $scheduledAt
    ]);
  }

  public function start(Campaign $campaign): void
  {
    if (!$campaign->status->canSend()) {
      throw new \Exception('Campaign cannot be sent in its current state.');
    }

    DB::transaction(function () use ($campaign) {
      $campaign->update([
        'status' => CampaignStatus::SENDING,
        'started_at' => now()
      ]);

      $this->queueCampaignBatches($campaign);
    });
  }

  public function pause(Campaign $campaign): void
  {
    if ($campaign->status !== CampaignStatus::SENDING) {
      throw new \Exception('Only sending campaigns can be paused.');
    }

    $campaign->update(['status' => CampaignStatus::PAUSED]);
  }

  public function resume(Campaign $campaign): void
  {
    if ($campaign->status !== CampaignStatus::PAUSED) {
      throw new \Exception('Only paused campaigns can be resumed.');
    }

    $campaign->update(['status' => CampaignStatus::SENDING]);
    $this->queueCampaignBatches($campaign);
  }

  private function queueCampaignBatches(Campaign $campaign): void
  {
    $settings = $campaign->team->getSetting('quota');
    $batchSize = $settings['batch_size'] ?? 500;

    $campaign->getRecipients()
      ->chunk($batchSize, function ($subscribers) use ($campaign) {
        SendCampaignBatch::dispatch($campaign, $subscribers);
      });
  }

  public function trackSend(Campaign $campaign, Subscriber $subscriber): void
  {
    $this->eventTracker->trackEvent(
      $campaign,
      $subscriber,
      EventType::SENT
    );
  }

  public function complete(Campaign $campaign): void
  {
    $campaign->update([
      'status' => CampaignStatus::SENT,
      'completed_at' => now()
    ]);
  }

  public function delete(Campaign $campaign): void
  {
    if (!in_array($campaign->status, ['draft', 'scheduled'])) {
      throw new \Exception('Only draft or scheduled campaigns can be deleted.');
    }

    $campaign->delete();
  }

  public function getDetailedStats(Campaign $campaign): array
  {
    $events = CampaignEvent::where('campaign_id', $campaign->id)
      ->select('type', DB::raw('count(*) as count'))
      ->groupBy('type')
      ->get()
      ->pluck('count', 'type')
      ->toArray();

    $total = $campaign->events()->count() ?: 1; // Prevent division by zero

    return [
      'delivered' => [
        'count' => $events['delivered'] ?? 0,
        'rate' => ($events['delivered'] ?? 0) / $total * 100
      ],
      'opened' => [
        'count' => $events['opened'] ?? 0,
        'rate' => ($events['opened'] ?? 0) / $total * 100
      ],
      'clicked' => [
        'count' => $events['clicked'] ?? 0,
        'rate' => ($events['clicked'] ?? 0) / $total * 100
      ],
      'bounced' => [
        'count' => $events['bounced'] ?? 0,
        'rate' => ($events['bounced'] ?? 0) / $total * 100
      ],
      'complaints' => [
        'count' => $events['complaint'] ?? 0,
        'rate' => ($events['complaint'] ?? 0) / $total * 100
      ],
    ];
  }

  public function getOpenRateOverTime(Campaign $campaign): array
  {
    return $this->getMetricOverTime($campaign, 'opened');
  }

  public function getClickRateOverTime(Campaign $campaign): array
  {
    return $this->getMetricOverTime($campaign, 'clicked');
  }

  public function getEngagementMetrics(Campaign $campaign): array
  {
    $events = CampaignEvent::where('campaign_id', $campaign->id)
      ->whereIn('type', ['opened', 'clicked'])
      ->select(
        'subscriber_id',
        DB::raw('COUNT(DISTINCT CASE WHEN type = "opened" THEN id END) as opens'),
        DB::raw('COUNT(DISTINCT CASE WHEN type = "clicked" THEN id END) as clicks')
      )
      ->groupBy('subscriber_id')
      ->get();

    return [
      'highly_engaged' => $events->filter(fn($e) => $e->opens > 2 && $e->clicks > 0)->count(),
      'moderately_engaged' => $events->filter(fn($e) => $e->opens > 0 && $e->clicks > 0)->count(),
      'low_engaged' => $events->filter(fn($e) => $e->opens > 0 && $e->clicks === 0)->count(),
      'not_engaged' => $events->filter(fn($e) => $e->opens === 0 && $e->clicks === 0)->count(),
    ];
  }

  protected function getMetricOverTime(Campaign $campaign, string $type): array
  {
    return CampaignEvent::where('campaign_id', $campaign->id)
      ->where('type', $type)
      ->select(
        DB::raw('DATE(created_at) as date'),
        DB::raw('COUNT(*) as count')
      )
      ->groupBy('date')
      ->orderBy('date')
      ->get()
      ->map(fn($event) => [
        'date' => $event->date,
        'count' => $event->count,
      ])
      ->toArray();
  }
}
