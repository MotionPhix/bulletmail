<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CampaignFilter
{
  protected array $allowedSorts = [
    'name',
    'status',
    'created_at',
    'scheduled_at',
    'sent_at',
    'recipient_count'
  ];

  protected array $allowedStatuses = [
    'draft',
    'scheduled',
    'sending',
    'sent',
    'failed'
  ];

  protected array $searchableFields = [
    'name',
    'subject',
    'from_name',
    'from_email'
  ];

  public function __construct(protected Request $request)
  {}

  public function apply(Builder $query): Builder
  {
    return $query
      ->when($this->request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          foreach ($this->searchableFields as $field) {
            $q->orWhere($field, 'like', "%{$search}%");
          }
        });
      })
      ->when(
        $this->request->status && in_array($this->request->status, $this->allowedStatuses),
        fn($query) => $query->where('status', $this->request->status)
      )
      ->when($this->request->date_from, function ($query, $date) {
        $query->whereDate('created_at', '>=', Carbon::parse($date));
      })
      ->when($this->request->date_to, function ($query, $date) {
        $query->whereDate('created_at', '<=', Carbon::parse($date));
      })
      ->when(
        $this->request->sort_by && in_array($this->request->sort_by, $this->allowedSorts),
        function ($query) {
          $direction = $this->request->sort_direction === 'desc' ? 'desc' : 'asc';
          $query->orderBy($this->request->sort_by, $direction);
        },
        fn($query) => $query->latest('id')
      );
  }

  public function getFilters(): array
  {
    return [
      'search' => $this->request->search,
      'status' => $this->request->status,
      'date_from' => $this->request->date_from,
      'date_to' => $this->request->date_to,
      'sort_by' => $this->request->sort_by ?? 'created_at',
      'sort_direction' => $this->request->sort_direction ?? 'desc',
    ];
  }

  public function getAllowedSorts(): array
  {
    return $this->allowedSorts;
  }

  public function getAllowedStatuses(): array
  {
    return $this->allowedStatuses;
  }
}
