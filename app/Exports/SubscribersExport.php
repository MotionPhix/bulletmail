<?php

namespace App\Exports;

use App\Models\{Team, Subscriber};
use Maatwebsite\Excel\Concerns\{
  FromQuery,
  Exportable,
  WithMapping,
  WithHeadings,
  WithStyles
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubscribersExport implements FromQuery, WithMapping, WithHeadings, WithStyles
{
  use Exportable;

  protected Team $team;
  protected array $filters;
  protected array $customFields;

  public function __construct(Team $team, array $filters = [])
  {
    $this->team = $team;
    $this->filters = $filters;
    $this->customFields = $this->getCustomFields();
  }

  public function query()
  {
    $query = Subscriber::query()
      ->where('team_id', $this->team->id)
      ->with(['mailingLists']);

    // Apply filters
    if (!empty($this->filters['search'])) {
      $query->where(function ($q) {
        $q->where('email', 'like', "%{$this->filters['search']}%")
          ->orWhere('first_name', 'like', "%{$this->filters['search']}%")
          ->orWhere('last_name', 'like', "%{$this->filters['search']}%");
      });
    }

    if (!empty($this->filters['status'])) {
      $query->where('status', $this->filters['status']);
    }

    if (!empty($this->filters['list_id'])) {
      $query->whereHas('mailingLists', function ($q) {
        $q->where('mailing_list_id', $this->filters['list_id']);
      });
    }

    if (!empty($this->filters['segment_id'])) {
      // Apply segment conditions
      $segment = $this->team->segments()->find($this->filters['segment_id']);
      if ($segment) {
        $query->where(function ($q) use ($segment) {
          foreach ($segment->conditions as $condition) {
            $segment->applyCondition($q, $condition);
          }
        });
      }
    }

    return $query;
  }

  public function map($subscriber): array
  {
    $data = [
      $subscriber->email,
      $subscriber->first_name,
      $subscriber->last_name,
      $subscriber->status->value,
      $subscriber->mailingLists->pluck('name')->implode(', '),
      $subscriber->subscribed_at?->format('Y-m-d H:i:s'),
      $subscriber->emails_received,
      $subscriber->emails_opened,
      $subscriber->emails_clicked,
      $subscriber->engagement_score,
      $subscriber->source,
      $subscriber->created_at->format('Y-m-d H:i:s')
    ];

    // Add custom fields
    foreach ($this->customFields as $field) {
      $data[] = $subscriber->getCustomField($field);
    }

    return $data;
  }

  public function headings(): array
  {
    $headers = [
      'Email',
      'First Name',
      'Last Name',
      'Status',
      'Lists',
      'Subscribed Date',
      'Emails Received',
      'Emails Opened',
      'Emails Clicked',
      'Engagement Score',
      'Source',
      'Created Date'
    ];

    // Add custom field headers
    return array_merge($headers, array_map('ucwords', $this->customFields));
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }

  protected function getCustomFields(): array
  {
    return $this->team->subscribers()
      ->whereNotNull('custom_fields')
      ->get()
      ->pluck('custom_fields')
      ->flatten(1)
      ->keys()
      ->unique()
      ->sort()
      ->values()
      ->all();
  }
}
