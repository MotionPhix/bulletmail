<?php

namespace App\Models;

use App\Traits\{HasTeamScope, HasUuid};
use Illuminate\Database\Eloquent\{Model, SoftDeletes, Factories\HasFactory};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
  use HasFactory, SoftDeletes, HasTeamScope, HasUuid;

  protected $fillable = [
    'team_id',
    'name',
    'description',
    'conditions'
  ];

  protected $casts = [
    'conditions' => 'json'
  ];

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  protected function buildSegmentQuery()
  {
    $query = Subscriber::query()->forTeam($this->team);

    foreach ($this->conditions as $group) {
      $query->where(function ($query) use ($group) {
        foreach ($group['conditions'] as $condition) {
          $method = $group['match'] === 'any' ? 'orWhere' : 'where';
          $this->applyCondition($query, $condition, $method);
        }
      });
    }

    return $query;
  }

  protected function applyCondition($query, array $condition, string $method = 'where'): void
  {
    $field = $condition['field'];
    $operator = $condition['operator'];
    $value = $condition['value'];

    switch ($operator) {
      case 'equals':
        $query->$method($field, '=', $value);
        break;
      case 'not_equals':
        $query->$method($field, '!=', $value);
        break;
      case 'contains':
        $query->$method($field, 'LIKE', "%{$value}%");
        break;
      case 'not_contains':
        $query->$method($field, 'NOT LIKE', "%{$value}%");
        break;
      case 'starts_with':
        $query->$method($field, 'LIKE', "{$value}%");
        break;
      case 'ends_with':
        $query->$method($field, 'LIKE', "%{$value}");
        break;
      case 'is_empty':
        $query->$method(function ($q) use ($field) {
          $q->whereNull($field)->orWhere($field, '');
        });
        break;
      case 'is_not_empty':
        $query->$method(function ($q) use ($field) {
          $q->whereNotNull($field)->where($field, '!=', '');
        });
        break;
      case 'in_list':
        $query->$method(function ($q) use ($field, $value) {
          $q->whereIn($field, (array)$value);
        });
        break;
      case 'not_in_list':
        $query->$method(function ($q) use ($field, $value) {
          $q->whereNotIn($field, (array)$value);
        });
        break;
    }
  }

  public function previewSubscribers(int $limit = 10)
  {
    return $this->buildSegmentQuery()->limit($limit)->get();
  }

  public function getMatchingSubscribersCount(): int
  {
    return $this->buildSegmentQuery()->count();
  }
}
