<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait HasAnalytics
{
  public function getEmailAnalytics(string $period = '30d'): array
  {
    $range = $this->getDateRange($period);

    $stats = $this->campaigns()
      ->with('stats')
      ->whereBetween('created_at', [$range['start'], $range['end']])
      ->get()
      ->map(function ($campaign) {
        return [
          'campaign_id' => $campaign->id,
          'name' => $campaign->name,
          'sent' => $campaign->stats?->recipients_count ?? 0,
          'delivered' => $campaign->stats?->delivered_count ?? 0,
          'opened' => $campaign->stats?->opened_count ?? 0,
          'clicked' => $campaign->stats?->clicked_count ?? 0,
          'bounced' => $campaign->stats?->bounced_count ?? 0,
          'complained' => $campaign->stats?->complained_count ?? 0,
          'sent_at' => $campaign->sent_at
        ];
      });

    return [
      'summary' => $this->calculateSummary($stats),
      'trends' => $this->calculateTrends($stats, $range['start'], $range['end']),
      'campaigns' => $stats
    ];
  }

  protected function getDateRange(string $period): array
  {
    $end = now();
    $start = match($period) {
      '7d' => now()->subDays(7),
      '30d' => now()->subDays(30),
      '90d' => now()->subDays(90),
      'ytd' => now()->startOfYear(),
      default => now()->subDays(30)
    };

    return [
      'start' => $start,
      'end' => $end
    ];
  }

  protected function calculateSummary($stats): array
  {
    return [
      'total_sent' => $stats->sum('sent'),
      'total_delivered' => $stats->sum('delivered'),
      'total_opened' => $stats->sum('opened'),
      'total_clicked' => $stats->sum('clicked'),
      'average_open_rate' => $stats->avg('opened') ?? 0,
      'average_click_rate' => $stats->avg('clicked') ?? 0,
    ];
  }

  protected function calculateTrends($stats, $start, $end): array
  {
    return [
      'daily' => $stats
        ->groupBy(fn ($stat) => $stat['sent_at']->format('Y-m-d'))
        ->map(fn ($group) => [
          'sent' => $group->sum('sent'),
          'opened' => $group->sum('opened'),
          'clicked' => $group->sum('clicked')
        ])
    ];
  }
}
