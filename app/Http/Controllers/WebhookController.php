<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
  public function handleSendGridEvents(Request $request)
  {
    $events = $request->all();

    foreach ($events as $event) {
      try {
        // Validate event data
        if (!isset($event['email']) || !isset($event['event']) || !isset($event['timestamp'])) {
          continue;
        }

        // Extract campaign ID from custom args
        $campaignId = $event['campaign_id'] ?? null;

        if (!$campaignId) {
          Log::warning('Campaign ID not found in webhook event', ['event' => $event]);
          continue;
        }

        // Find campaign and subscriber
        $campaign = Campaign::find($campaignId);
        $subscriber = Subscriber::where('email', $event['email'])->first();

        if (!$campaign || !$subscriber) {
          Log::warning('Campaign or subscriber not found', [
            'campaign_id' => $campaignId,
            'email' => $event['email']
          ]);

          continue;
        }

        // Map SendGrid event types to our event types
        $eventType = $this->mapEventType($event['event']);

        // Create campaign event
        CampaignEvent::create([
          'campaign_id' => $campaign->id,
          'subscriber_id' => $subscriber->id,
          'type' => $eventType,
          'metadata' => [
            'ip' => $event['ip'] ?? null,
            'user_agent' => $event['useragent'] ?? null,
            'sg_event_id' => $event['sg_event_id'] ?? null,
            'sg_message_id' => $event['sg_message_id'] ?? null,
            'timestamp' => $event['timestamp'],
            'url' => $event['url'] ?? null,
            'category' => $event['category'] ?? [],
            'reason' => $event['reason'] ?? null,
            'status' => $event['status'] ?? null,
            'raw' => $event
          ]
        ]);

      } catch (\Exception $e) {
        Log::error('Error processing SendGrid webhook event', [
          'error' => $e->getMessage(),
          'event' => $event
        ]);
      }
    }

    return response()->json(['message' => 'Events processed']);
  }

  private function mapEventType(string $sendgridEvent): string
  {
    return match ($sendgridEvent) {
      'processed' => 'queued',
      'delivered' => 'delivered',
      'open' => 'opened',
      'click' => 'clicked',
      'bounce' => 'bounced',
      'dropped' => 'failed',
      'spamreport' => 'complaint',
      'unsubscribe' => 'unsubscribed',
      default => 'other'
    };
  }
}
