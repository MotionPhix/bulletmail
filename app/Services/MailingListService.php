<?php

namespace App\Services;

use App\Models\{MailingList, Team};
use App\Exports\ListSubscribersExport;
use Illuminate\Support\Facades\{DB, Queue};
use Maatwebsite\Excel\Facades\Excel;

class MailingListService
{
  public function create(Team $team, array $data): MailingList
  {
    return DB::transaction(function () use ($team, $data) {
      return $team->mailingLists()->create(array_merge($data, [
        'user_id' => auth()->id(),
        'type' => 'standard'
      ]));
    });
  }

  public function update(MailingList $list, array $data): MailingList
  {
    $list->update($data);
    return $list->fresh();
  }

  public function delete(MailingList $list): void
  {
    DB::transaction(function () use ($list) {
      // Detach all subscribers
      $list->subscribers()->detach();
      $list->delete();
    });
  }

  public function duplicate(MailingList $list): MailingList
  {
    return DB::transaction(function () use ($list) {
      $newList = $list->replicate();
      $newList->name = "{$list->name} (Copy)";
      $newList->subscriber_count = 0;
      $newList->save();

      // Copy segment rules if any
      if ($list->segment_rules) {
        $newList->update(['segment_rules' => $list->segment_rules]);
      }

      return $newList;
    });
  }

  public function synchronize(MailingList $list): void
  {
    if (!$list->segment_rules) {
      return;
    }

    Queue::push(new SynchronizeMailingList($list));
  }

  public function getStats(MailingList $list): array
  {
    $subscribers = $list->subscribers();

    return [
      'total' => $subscribers->count(),
      'active' => $subscribers->active()->count(),
      'unsubscribed' => $subscribers->unsubscribed()->count(),
      'bounced' => $subscribers->bounced()->count(),
      'engaged_30d' => $subscribers->engaged(30)->count(),
      'unengaged_30d' => $subscribers->unengaged(30)->count(),
      'growth_trend' => $this->getGrowthTrend($list),
      'engagement_metrics' => $this->getEngagementMetrics($list)
    ];
  }

  public function addSubscribers(MailingList $list, array $subscriberIds): void
  {
    $subscribers = $list->team->subscribers()
      ->whereIn('id', $subscriberIds)
      ->whereDoesntHave('mailingLists', function ($query) use ($list) {
        $query->where('mailing_list_id', $list->id);
      })
      ->get();

    foreach ($subscribers as $subscriber) {
      $subscriber->addToList($list);
    }

    $list->updateQuietly(['subscriber_count' => $list->subscribers()->count()]);
  }

  public function removeSubscribers(MailingList $list, array $subscriberIds): void
  {
    DB::transaction(function () use ($list, $subscriberIds) {
      $list->subscribers()->detach($subscriberIds);
      $list->updateQuietly(['subscriber_count' => $list->subscribers()->count()]);
    });
  }

  public function export(MailingList $list)
  {
    return Excel::download(
      new ListSubscribersExport($list),
      "list-{$list->id}-subscribers-" . now()->format('Y-m-d') . '.xlsx'
    );
  }

  protected function getGrowthTrend(MailingList $list): array
  {
    return $list->subscribers()
      ->select(DB::raw('DATE_FORMAT(mailing_list_subscriber.created_at, "%Y-%m") as month'), DB::raw('count(*) as count'))
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('count', 'month')
      ->toArray();
  }

  protected function getEngagementMetrics(MailingList $list): array
  {
    $totalSubscribers = $list->subscribers()->count() ?: 1;
    $openedCount = $list->subscribers()->where('emails_opened', '>', 0)->count();
    $clickedCount = $list->subscribers()->where('emails_clicked', '>', 0)->count();

    return [
      'open_rate' => round(($openedCount / $totalSubscribers) * 100, 2),
      'click_rate' => round(($clickedCount / $totalSubscribers) * 100, 2),
    ];
  }
}
