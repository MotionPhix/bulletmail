<?php

namespace App\Http\Controllers\SendGrid;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
  public function handle(Request $request)
  {
    $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
    $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

    if (!$this->validateWebhook($signature, $timestamp, $request->getContent())) {
      return response()->json(['error' => 'Invalid signature'], 401);
    }

    collect($request->all())->each(function ($event) {
      $this->processEvent($event);
    });

    return response()->json(['message' => 'Processed']);
  }

  protected function processEvent(array $event)
  {
    try {
      $campaignId = $event['campaign_id'] ?? null;
      $email = $event['email'] ?? null;

      if (!$campaignId || !$email) {
        return;
      }

      $campaign = Campaign::find($campaignId);
      $subscriber = Subscriber::firstWhere('email', $email);

      if (!$campaign || !$subscriber) {
        return;
      }

      CampaignEvent::create([
        'campaign_id' => $campaign->id,
        'subscriber_id' => $subscriber->id,
        'type' => $this->mapEventType($event['event']),
        'metadata' => [
          'ip' => $event['ip'] ?? null,
          'user_agent' => $event['useragent'] ?? null,
          'timestamp' => $event['timestamp'] ?? null,
          'url' => $event['url'] ?? null,
        ],
      ]);

      $this->updateCampaignStats($campaign, $event['event']);
    } catch (\Exception $e) {
      Log::error('Failed to process SendGrid event', [
        'error' => $e->getMessage(),
        'event' => $event,
      ]);
    }
  }

  protected function mapEventType(string $sendgridEvent): string
  {
    return match ($sendgridEvent) {
      'delivered' => CampaignEvent::TYPE_DELIVERED,
      'open' => CampaignEvent::TYPE_OPENED,
      'click' => CampaignEvent::TYPE_CLICKED,
      'bounce' => CampaignEvent::TYPE_BOUNCED,
      'spamreport' => CampaignEvent::TYPE_COMPLAINED,
      'unsubscribe' => CampaignEvent::TYPE_UNSUBSCRIBED,
      default => CampaignEvent::TYPE_SENT,
    };
  }

  protected function validateWebhook(string $signature, string $timestamp, string $body): bool
  {
    $secret = config('services.sendgrid.webhook_secret');
    $payload = $timestamp . $body;
    $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

    return hash_equals($expectedSignature, $signature);
  }

  protected function updateCampaignStats(Campaign $campaign, string $event)
  {
    $stats = $campaign->stats;

    match ($event) {
      'delivered' => $stats->increment('delivered_count'),
      'open' => $stats->increment('opened_count'),
      'click' => $stats->increment('clicked_count'),
      'bounce' => $stats->increment('bounced_count'),
      'spamreport' => $stats->increment('complained_count'),
      'unsubscribe' => $stats->increment('unsubscribed_count'),
      default => null,
    };
  }
}
