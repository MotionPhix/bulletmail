<?php

namespace App\Services;

use App\Models\{Segment, Team};
use Illuminate\Support\Facades\DB;

class SegmentService
{
  public function create(Team $team, array $data): Segment
  {
    return DB::transaction(function () use ($team, $data) {
      $segment = $team->segments()->create([
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'conditions' => $data['conditions'] ?? [],
        'rules' => $data['rules'] ?? null,
        'user_id' => auth()->id()
      ]);

      // Calculate initial subscriber count
      $count = $segment->previewSubscribers()->count();
      $segment->update(['subscriber_count' => $count]);

      return $segment;
    });
  }

  public function update(Segment $segment, array $data): Segment
  {
    DB::transaction(function () use ($segment, $data) {
      $segment->update($data);

      // Recalculate subscriber count if conditions changed
      if (isset($data['conditions'])) {
        $count = $segment->previewSubscribers()->count();
        $segment->update([
          'subscriber_count' => $count,
          'last_calculated_at' => now()
        ]);
      }
    });

    return $segment->fresh();
  }

  public function delete(Segment $segment): void
  {
    $segment->delete();
  }

  public function duplicate(Segment $segment): Segment
  {
    return DB::transaction(function () use ($segment) {
      $newSegment = $segment->replicate();
      $newSegment->name = "{$segment->name} (Copy)";
      $newSegment->subscriber_count = $segment->subscriber_count;
      $newSegment->save();

      return $newSegment;
    });
  }

  public function getSubscribers(Segment $segment, array $options = [])
  {
    $query = $segment->buildSegmentQuery();

    if (!empty($options['sort'])) {
      $query->orderBy($options['sort'], $options['direction'] ?? 'asc');
    }

    return $query->paginate($options['per_page'] ?? 10);
  }

  public function getStats(Segment $segment): array
  {
    $subscribers = $segment->buildSegmentQuery();

    return [
      'total' => $subscribers->count(),
      'active' => $subscribers->active()->count(),
      'unsubscribed' => $subscribers->unsubscribed()->count(),
      'engaged_30d' => $subscribers->engaged(30)->count(),
      'unengaged_30d' => $subscribers->unengaged(30)->count(),
      'average_open_rate' => $this->calculateAverageOpenRate($subscribers),
      'average_click_rate' => $this->calculateAverageClickRate($subscribers),
      'growth_trend' => $this->getGrowthTrend($segment)
    ];
  }

  protected function calculateAverageOpenRate($query): float
  {
    $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(emails_received) as total_received,
            SUM(emails_opened) as total_opened
        ')->first();

    if ($stats->total_received === 0) return 0;
    return round(($stats->total_opened / $stats->total_received) * 100, 2);
  }

  protected function calculateAverageClickRate($query): float
  {
    $stats = $query->selectRaw('
            COUNT(*) as total,
            SUM(emails_received) as total_received,
            SUM(emails_clicked) as total_clicked
        ')->first();

    if ($stats->total_received === 0) return 0;
    return round(($stats->total_clicked / $stats->total_received) * 100, 2);
  }

  protected function getGrowthTrend(Segment $segment): array
  {
    return $segment->buildSegmentQuery()
      ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('count', 'month')
      ->toArray();
  }
}
