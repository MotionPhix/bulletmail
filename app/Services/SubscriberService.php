<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
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

  public function import(Team $team, UploadedFile $file, array $options = []): array
  {
    $results = [
      'total' => 0,
      'created' => 0,
      'updated' => 0,
      'failed' => 0,
      'errors' => []
    ];

    try {
      $import = new class($team, $options['update_existing'] ?? false, $results) implements
        \Maatwebsite\Excel\Concerns\ToCollection,
        \Maatwebsite\Excel\Concerns\WithHeadingRow,
        \Maatwebsite\Excel\Concerns\WithChunkReading {

        private $team;
        private $updateExisting;
        private $importResults;

        public function __construct(Team $team, bool $updateExisting, array &$results)
        {
          $this->team = $team;
          $this->updateExisting = $updateExisting;
          $this->importResults = &$results;
        }

        public function collection(Collection $collection)
        {
          foreach ($collection as $row) {
            $this->importResults['total']++;

            try {
              // Handle case-insensitive headers
              $email = $row['email'] ?? $row['Email'] ?? $row['EMAIL'] ?? null;
              $firstName = $row['first_name'] ?? $row['First Name'] ?? $row['FIRST_NAME'] ?? null;
              $lastName = $row['last_name'] ?? $row['Last Name'] ?? $row['LAST_NAME'] ?? null;
              $status = $row['status'] ?? $row['Status'] ?? $row['STATUS'] ?? null;

              // Validate required fields and email format
              if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid or missing email address');
              }

              if (empty($firstName) || empty($lastName)) {
                throw new \Exception('Missing required fields (first_name or last_name)');
              }

              $data = [
                'email' => strtolower(trim($email)),
                'first_name' => trim($firstName),
                'last_name' => trim($lastName),
                'status' => $status ?? SubscriberStatus::SUBSCRIBED->value
              ];

              $existingSubscriber = $this->team->subscribers()
                ->where('email', $data['email'])
                ->first();

              if ($existingSubscriber) {
                if ($this->updateExisting) {
                  $existingSubscriber->update($data);
                  $this->importResults['updated']++;
                }
              } else {
                $this->team->subscribers()->create(array_merge($data, [
                  'user_id' => auth()->id(),
                  'source' => 'import',
                  'subscribed_at' => now(),
                  'ip_address' => request()->ip()
                ]));
                $this->importResults['created']++;
              }
            } catch (\Exception $e) {
              $this->importResults['failed']++;
              $this->importResults['errors'][] = sprintf(
                'Row %d: %s (Email: %s)',
                $this->importResults['total'],
                $e->getMessage(),
                $email ?? 'unknown'
              );
            }
          }
        }

        public function chunkSize(): int
        {
          return 1000;
        }
      };

      // Wrap import in database transaction for data integrity
      DB::transaction(function() use ($import, $file) {
        Excel::import($import, $file);
      });

      return [
        'success' => true,
        'message' => sprintf(
          'Import completed: %d processed, %d created, %d updated, %d failed',
          $results['total'],
          $results['created'],
          $results['updated'],
          $results['failed']
        ),
        'data' => $results
      ];

    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Import failed: ' . $e->getMessage(),
        'errors' => [$e->getMessage()]
      ];
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
