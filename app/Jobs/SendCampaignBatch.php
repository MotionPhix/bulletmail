<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\CampaignService;
use App\Services\SendGrid\SendGridService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendCampaignBatch implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    private Campaign $campaign,
    private Collection $subscribers
  ) {}

  public function handle(SendGridService $sendgrid, CampaignService $campaignService): void
  {
    foreach ($this->subscribers as $subscriber) {
      try {
        $success = $sendgrid->send($this->campaign, $subscriber);

        if ($success) {
          $campaignService->trackSend($this->campaign, $subscriber);
        }
      } catch (\Exception $e) {
        report($e);
        continue;
      }
    }
  }

  public function tags(): array
  {
    return ['campaign:' . $this->campaign->id];
  }

  public function retryUntil(): \DateTime
  {
    return now()->addHours(24);
  }
}
