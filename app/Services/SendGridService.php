<?php

namespace App\Services;

use SendGrid;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\SendGridException;
use SendGrid\Mail\Mail;

class SendGridService
{
  protected $client;
  protected $apiKey;

  public function __construct()
  {
    $this->apiKey = config('services.sendgrid.api_key');
    $this->client = new SendGrid($this->apiKey);
  }

  /**
   * Get campaign statistics
   *
   * @param string $campaignId
   * @return array
   * @throws SendGridException
   */
  public function getCampaignStats($campaignId)
  {
    try {
      $cacheKey = "campaign_stats_{$campaignId}";

      return Cache::remember($cacheKey, 300, function () use ($campaignId) {
        $response = $this->client->client->campaigns()->_($campaignId)->stats()->get();

        if ($response->statusCode() !== 200) {
          throw new SendGridException('Failed to fetch campaign stats');
        }

        $stats = json_decode($response->body(), true);

        // Get geographic data
        $geoStats = $this->getGeographicStats($campaignId);

        // Get device stats
        $deviceStats = $this->getDeviceStats($campaignId);

        // Get time series data
        $timeStats = $this->getTimeSeriesStats($campaignId);

        return [
          'opens' => $stats['stats'][0]['opens'] ?? 0,
          'clicks' => $stats['stats'][0]['clicks'] ?? 0,
          'bounces' => $stats['stats'][0]['bounces'] ?? 0,
          'unsubscribes' => $stats['stats'][0]['unsubscribes'] ?? 0,
          'complaints' => $stats['stats'][0]['spam_reports'] ?? 0,
          'recipients' => $stats['stats'][0]['recipients'] ?? 0,
          'deliveryRate' => $this->calculateRate($stats['stats'][0]['delivered'] ?? 0, $stats['stats'][0]['recipients'] ?? 0),
          'openRate' => $this->calculateRate($stats['stats'][0]['unique_opens'] ?? 0, $stats['stats'][0]['delivered'] ?? 0),
          'clickRate' => $this->calculateRate($stats['stats'][0]['unique_clicks'] ?? 0, $stats['stats'][0]['delivered'] ?? 0),
          'geoStats' => $geoStats,
          'deviceStats' => $deviceStats,
          'timeStats' => $timeStats,
        ];
      });
    } catch (\Exception $e) {
      throw new SendGridException('Error fetching campaign stats: ' . $e->getMessage());
    }
  }

  /**
   * Get geographic stats for a campaign
   */
  private function getGeographicStats($campaignId)
  {
    $response = $this->client->client->geo_stats()
      ->get(null, [
        'campaign_id' => $campaignId,
        'aggregated_by' => 'country',
      ]);

    if ($response->statusCode() !== 200) {
      return [];
    }

    $stats = json_decode($response->body(), true);
    return collect($stats['stats'] ?? [])->map(function ($stat) {
      return [
        'country' => $stat['country'],
        'opens' => $stat['opens'] ?? 0,
        'clicks' => $stat['clicks'] ?? 0,
      ];
    })->toArray();
  }

  /**
   * Get device type stats for a campaign
   */
  private function getDeviceStats($campaignId)
  {
    $response = $this->client->client->devices_stats()
      ->get(null, ['campaign_id' => $campaignId]);

    if ($response->statusCode() !== 200) {
      return [];
    }

    $stats = json_decode($response->body(), true);
    return collect($stats['stats'] ?? [])->map(function ($stat) {
      return [
        'device' => $stat['device_type'],
        'count' => $stat['opens'],
      ];
    })->toArray();
  }

  /**
   * Get time series stats for a campaign
   */
  private function getTimeSeriesStats($campaignId)
  {
    $response = $this->client->client->campaigns()->_($campaignId)
      ->stats()->get(null, ['aggregated_by' => 'hour']);

    if ($response->statusCode() !== 200) {
      return [];
    }

    $stats = json_decode($response->body(), true);
    return collect($stats['stats'] ?? [])->map(function ($stat) {
      return [
        'timestamp' => Carbon::parse($stat['date']),
        'opens' => $stat['opens'] ?? 0,
        'clicks' => $stat['clicks'] ?? 0,
      ];
    })->toArray();
  }

  /**
   * Calculate rate as percentage
   */
  private function calculateRate($numerator, $denominator)
  {
    if ($denominator == 0) return 0;
    return ($numerator / $denominator) * 100;
  }

  /**
   * Send email via SendGrid with tracking
   *
   * @param Mail $email
   * @param array $trackingData
   * @return object
   * @throws SendGridException
   */
  public function send(Mail $email, array $trackingData = [])
  {
    try {
      // Add custom tracking settings
      $email->addCustomArg('campaign_id', $trackingData['campaign_id']);

      // Add click tracking
      $trackingSettings = [
        'click_tracking' => ['enable' => true, 'enable_text' => true],
        'open_tracking' => ['enable' => true],
        'subscription_tracking' => ['enable' => true],
      ];

      foreach ($trackingSettings as $setting => $value) {
        $email->setTrackingSettings($value);
      }

      // Add categories for better filtering
      $email->addCategory('campaign_' . $trackingData['campaign_id']);

      if (!empty($trackingData['tags'])) {
        foreach ($trackingData['tags'] as $tag) {
          $email->addCategory($tag);
        }
      }

      $response = $this->client->send($email);

      if ($response->statusCode() >= 400) {
        throw new SendGridException(
          'Failed to send email: ' . json_decode($response->body())->errors[0]->message ?? 'Unknown error'
        );
      }

      return $response;
    } catch (\Exception $e) {
      throw new SendGridException('Error sending email: ' . $e->getMessage());
    }
  }
}
