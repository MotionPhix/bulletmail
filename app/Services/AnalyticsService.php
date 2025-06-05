<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campaign;
use App\Models\Subscriber;
use Carbon\Carbon;

class AnalyticsService
{
  protected User $user;

  public function setUser(User $user): self
  {
    $this->user = $user;
    return $this;
  }

  public function getDashboardStats(string $startDate, string $endDate): array
  {
    $team = $this->user->currentTeam;
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);

    return [
      'subscriberStats' => $this->getSubscriberStats($team->id, $start, $end),
      'emailClients' => $this->getEmailClientDistribution($team->id),
      'geoData' => $this->getGeographicDistribution($team->id),
      'campaigns' => $this->getCampaignPerformance($team->id, $start, $end),
      'subscribers' => $this->getSubscriberGrowth($team->id, $start, $end)
    ];
  }

  protected function getSubscriberStats(int $teamId, Carbon $start, Carbon $end): array
  {
    $total = Subscriber::where('team_id', $teamId)->count();
    $active = Subscriber::where('team_id', $teamId)
      ->where('status', 'subscribed')
      ->count();

    $previousPeriodStart = (clone $start)->subDays($end->diffInDays($start));
    $previousTotal = Subscriber::where('team_id', $teamId)
      ->where('created_at', '<=', $previousPeriodStart)
      ->count();

    $growth = $previousTotal > 0
      ? (($total - $previousTotal) / $previousTotal) * 100
      : 0;

    return [
      'total' => $total,
      'active' => $active,
      'unsubscribed' => Subscriber::where('team_id', $teamId)
        ->where('status', 'unsubscribed')
        ->count(),
      'bounced' => Subscriber::where('team_id', $teamId)
        ->where('status', 'bounced')
        ->count(),
      'growth' => round($growth, 1),
      'openRate' => $this->calculateAverageOpenRate($teamId),
      'clickRate' => $this->calculateAverageClickRate($teamId)
    ];
  }

  protected function getEmailClientDistribution(int $teamId): array
  {
    // Simulated data - replace with actual tracking data
    return [
      'gmail' => 45,
      'outlook' => 25,
      'apple' => 20,
      'yahoo' => 7,
      'other' => 3
    ];
  }

  protected function getGeographicDistribution(int $teamId): array
  {
    // Replace with actual tracking data
    return [
      'countries' => ['United States', 'United Kingdom', 'Canada', 'Australia', 'Germany'],
      'counts' => [45, 20, 15, 12, 8]
    ];
  }

  protected function getCampaignPerformance(int $teamId, Carbon $start, Carbon $end): array
  {
    $campaigns = Campaign::where('team_id', $teamId)
      ->whereBetween('created_at', [$start, $end])
      ->with('stats')
      ->latest()
      ->limit(10)  // Limit to last 10 campaigns for better visualization
      ->get();

    return [
      'campaigns' => $campaigns->map(fn($campaign) => [
        'id' => $campaign->id,
        'name' => $campaign->name,
        'sent' => $campaign->stats?->recipients_count ?? 0,
        'opened' => $campaign->stats?->opened_count ?? 0,
        'clicked' => $campaign->stats?->clicked_count ?? 0,
        'bounced' => $campaign->stats?->bounced_count ?? 0,
        'createdAt' => $campaign->created_at->format('Y-m-d'),
        'openRate' => $campaign->stats?->getOpenRate() ?? 0,
        'clickRate' => $campaign->stats?->getClickRate() ?? 0,
        'bounceRate' => $campaign->stats?->getBounceRate() ?? 0
      ])->toArray(),
      'totals' => [
        'sent' => $campaigns->sum('stats.recipients_count'),
        'opened' => $campaigns->sum('stats.opened_count'),
        'clicked' => $campaigns->sum('stats.clicked_count'),
        'bounced' => $campaigns->sum('stats.bounced_count')
      ]
    ];
  }

  protected function getSubscriberGrowth(int $teamId, Carbon $start, Carbon $end): array
  {
    $subscribers = Subscriber::where('team_id', $teamId)
      ->whereBetween('created_at', [$start, $end])
      ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
      ->groupBy('date')
      ->orderBy('date')
      ->get();

    return [
      'dates' => $subscribers->pluck('date')->toArray(),
      'counts' => $subscribers->pluck('count')->toArray()
    ];
  }

  protected function calculateAverageOpenRate(int $teamId): float
  {
    $campaigns = Campaign::where('team_id', $teamId)
      ->whereHas('stats')
      ->with('stats')
      ->get();

    if ($campaigns->isEmpty()) {
      return 0;
    }

    $totalRate = $campaigns->sum(function ($campaign) {
      $stats = $campaign->stats;
      return $stats->recipients_count > 0
        ? ($stats->opened_count / $stats->recipients_count) * 100
        : 0;
    });

    return round($totalRate / $campaigns->count(), 1);
  }

  protected function calculateAverageClickRate(int $teamId): float
  {
    $campaigns = Campaign::where('team_id', $teamId)
      ->whereHas('stats')
      ->with('stats')
      ->get();

    if ($campaigns->isEmpty()) {
      return 0;
    }

    $totalRate = $campaigns->sum(function ($campaign) {
      $stats = $campaign->stats;
      return $stats->recipients_count > 0
        ? ($stats->clicked_count / $stats->recipients_count) * 100
        : 0;
    });

    return round($totalRate / $campaigns->count(), 1);
  }
}
