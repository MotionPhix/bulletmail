<?php

namespace App\Services;

use App\Models\{Subscriber, Team, MailingList};
use App\Enums\SubscriberStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\Rule;

class SubscriberService
{
  public function create(Team $team, array $data): Subscriber
  {
    return DB::transaction(function () use ($team, $data) {
      $subscriber = $team->subscribers()->create(array_merge($data, [
        'user_id' => auth()->id(),
        'status' => SubscriberStatus::SUBSCRIBED,
        'subscribed_at' => now(),
        'source' => $data['source'] ?? 'manual',
        'ip_address' => request()->ip()
      ]));

      // Add to mailing lists if specified
      if (!empty($data['mailing_lists'])) {
        foreach ($data['mailing_lists'] as $listId) {
          if ($list = $team->mailingLists()->find($listId)) {
            $subscriber->addToList($list);
          }
        }
      }

      return $subscriber;
    });
  }

  public function update(Subscriber $subscriber, array $data): Subscriber
  {
    return DB::transaction(function () use ($subscriber, $data) {
      // Handle status change
      if (isset($data['status']) && $data['status'] !== $subscriber->status) {
        $this->handleStatusChange($subscriber, $data['status']);
      }

      $subscriber->update($data);

      // Update mailing list associations if provided
      if (isset($data['mailing_lists'])) {
        $subscriber->mailingLists()->sync($data['mailing_lists']);
      }

      return $subscriber->refresh();
    });
  }

  public function bulkUpdate(Collection $subscribers, array $data): void
  {
    DB::transaction(function () use ($subscribers, $data) {
      foreach ($subscribers as $subscriber) {
        $this->update($subscriber, $data);
      }
    });
  }

  public function import(Team $team, array $rows, array $options = []): array
  {
    $results = [
      'total' => 0,
      'imported' => 0,
      'updated' => 0,
      'failed' => 0,
      'errors' => []
    ];

    DB::transaction(function () use ($team, $rows, $options, &$results) {
      foreach ($rows as $index => $row) {
        $results['total']++;

        try {
          $this->processImportRow($team, $row, $options, $results, $index + 2);
        } catch (\Exception $e) {
          $results['failed']++;
          $results['errors'][] = [
            'row' => $index + 2,
            'data' => $row,
            'errors' => ['system' => [$e->getMessage()]]
          ];
        }
      }
    });

    return $results;
  }

  protected function processImportRow(Team $team, array $data, array $options, array &$results, int $rowNumber): void
  {
    // Validate the row
    $validator = Validator::make($data, [
      'email' => ['required', 'email', Rule::unique('subscribers', 'email')
        ->where('team_id', $team->id)
        ->ignore(optional(Subscriber::where('email', $data['email'])
          ->where('team_id', $team->id)
          ->first())->id)],
      'first_name' => 'required|string|max:255',
      'last_name' => 'required|string|max:255',
      'status' => ['nullable', Rule::in(array_column(SubscriberStatus::cases(), 'value'))]
    ]);

    if ($validator->fails()) {
      $results['failed']++;
      $results['errors'][] = [
        'row' => $rowNumber,
        'data' => $data,
        'errors' => $validator->errors()->toArray()
      ];
      return;
    }

    // Set default status if not provided
    $data['status'] ??= SubscriberStatus::SUBSCRIBED->value;

    // Try to find existing subscriber
    $subscriber = $team->subscribers()
      ->where('email', $data['email'])
      ->first();

    if ($subscriber) {
      if ($options['update_existing'] ?? true) {
        $this->update($subscriber, $data);
        $results['updated']++;
      }
    } else {
      $this->create($team, $data);
      $results['imported']++;
    }
  }

  protected function handleStatusChange(Subscriber $subscriber, string $newStatus): void
  {
    $method = match ($newStatus) {
      SubscriberStatus::UNSUBSCRIBED->value => 'unsubscribe',
      SubscriberStatus::SUBSCRIBED->value => 'resubscribe',
      SubscriberStatus::BOUNCED->value => 'markAsBounced',
      SubscriberStatus::COMPLAINED->value => 'markAsComplained',
      default => null
    };

    if ($method) {
      $subscriber->$method();
    }
  }

  public function getSubscribers(Team $team, array $filters = [], int $perPage = 10)
  {
    $query = $team->subscribers()->with('mailingLists');

    // Apply search filter
    if (!empty($filters['search'])) {
      $query->where(function ($q) use ($filters) {
        $q->where('email', 'like', "%{$filters['search']}%")
          ->orWhere('first_name', 'like', "%{$filters['search']}%")
          ->orWhere('last_name', 'like', "%{$filters['search']}%");
      });
    }

    // Apply status filter
    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    // Apply list filter
    if (!empty($filters['list_id'])) {
      $query->whereHas('mailingLists', function ($q) use ($filters) {
        $q->where('mailing_list_id', $filters['list_id']);
      });
    }

    // Apply segment filter
    if (!empty($filters['segment_id'])) {
      $segment = $team->segments()->findOrFail($filters['segment_id']);
      $query->whereIn('id', $segment->buildSegmentQuery()->select('id'));
    }

    // Apply sorting
    $sort = $filters['sort'] ?? 'created_at';
    $direction = $filters['direction'] ?? 'desc';
    $query->orderBy($sort, $direction);

    return $query->paginate($perPage);
  }

  public function getEngagementStats(Team $team): array
  {
    $subscribers = $team->subscribers();

    return [
      'total' => $subscribers->count(),
      'active' => $subscribers->active()->count(),
      'unsubscribed' => $subscribers->unsubscribed()->count(),
      'bounced' => $subscribers->bounced()->count(),
      'engaged_30d' => $subscribers->engaged(30)->count(),
      'unengaged_30d' => $subscribers->unengaged(30)->count(),
      'status_distribution' => $this->getStatusDistribution($team),
      'growth_trend' => $this->getGrowthTrend($team),
    ];
  }

  protected function getStatusDistribution(Team $team): array
  {
    return $team->subscribers()
      ->selectRaw('status, count(*) as count')
      ->groupBy('status')
      ->pluck('count', 'status')
      ->toArray();
  }

  protected function getGrowthTrend(Team $team): array
  {
    return $team->subscribers()
      ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, count(*) as count')
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('count', 'month')
      ->toArray();
  }
}
