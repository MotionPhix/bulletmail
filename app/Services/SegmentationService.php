<?php

namespace App\Services;

use App\Models\Subscriber;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SegmentationService
{
  protected $conditions = [];
  protected $query;

  public function __construct()
  {
    $this->query = Subscriber::query();
  }

  public function segment(array $criteria): Collection
  {
    foreach ($criteria as $rule) {
      $this->applyRule($rule);
    }

    return $this->query->get();
  }

  protected function applyRule(array $rule): void
  {
    $type = $rule['type'] ?? '';
    $operator = $rule['operator'] ?? '';
    $value = $rule['value'] ?? null;
    $field = $rule['field'] ?? '';

    switch ($type) {
      case 'profile':
        $this->applyProfileRule($field, $operator, $value);
        break;
      case 'behavior':
        $this->applyBehaviorRule($field, $operator, $value);
        break;
      case 'engagement':
        $this->applyEngagementRule($field, $operator, $value);
        break;
      case 'purchase':
        $this->applyPurchaseRule($field, $operator, $value);
        break;
      case 'custom':
        $this->applyCustomFieldRule($field, $operator, $value);
        break;
    }
  }

  protected function applyProfileRule(string $field, string $operator, $value): void
  {
    $this->query->where(function (Builder $query) use ($field, $operator, $value) {
      switch ($operator) {
        case 'equals':
          $query->where($field, $value);
          break;
        case 'not_equals':
          $query->where($field, '!=', $value);
          break;
        case 'contains':
          $query->where($field, 'LIKE', "%{$value}%");
          break;
        case 'starts_with':
          $query->where($field, 'LIKE', "{$value}%");
          break;
        case 'ends_with':
          $query->where($field, 'LIKE', "%{$value}");
          break;
      }
    });
  }

  protected function applyBehaviorRule(string $field, string $operator, $value): void
  {
    switch ($field) {
      case 'last_login':
        $this->applyDateRule('last_login_at', $operator, $value);
        break;
      case 'signup_date':
        $this->applyDateRule('created_at', $operator, $value);
        break;
      case 'email_opened':
        $this->query->whereHas('emailEvents', function (Builder $query) use ($operator, $value) {
          $query->where('type', 'open')
            ->having('count(*)', $this->convertOperator($operator), $value);
        });
        break;
      case 'email_clicked':
        $this->query->whereHas('emailEvents', function (Builder $query) use ($operator, $value) {
          $query->where('type', 'click')
            ->having('count(*)', $this->convertOperator($operator), $value);
        });
        break;
    }
  }

  protected function applyEngagementRule(string $field, string $operator, $value): void
  {
    switch ($field) {
      case 'campaign_engagement':
        $this->query->whereHas('campaignEvents', function (Builder $query) use ($operator, $value) {
          $query->where('created_at', '>=', Carbon::now()->subDays(30))
            ->having('count(*)', $this->convertOperator($operator), $value);
        });
        break;
      case 'website_visits':
        $this->query->whereHas('pageViews', function (Builder $query) use ($operator, $value) {
          $query->where('created_at', '>=', Carbon::now()->subDays(30))
            ->having('count(*)', $this->convertOperator($operator), $value);
        });
        break;
    }
  }

  protected function applyPurchaseRule(string $field, string $operator, $value): void
  {
    switch ($field) {
      case 'total_spent':
        $this->query->whereHas('orders', function (Builder $query) use ($operator, $value) {
          $query->select('subscriber_id')
            ->groupBy('subscriber_id')
            ->havingRaw('SUM(total_amount) ' . $this->convertOperator($operator) . ' ?', [$value]);
        });
        break;
      case 'purchase_count':
        $this->query->has('orders', $this->convertOperator($operator), $value);
        break;
      case 'last_purchase':
        $this->query->whereHas('orders', function (Builder $query) use ($operator, $value) {
          $query->where('created_at', $this->convertOperator($operator), Carbon::parse($value));
        });
        break;
      case 'product_category':
        $this->query->whereHas('orders.items.product', function (Builder $query) use ($value) {
          $query->where('category_id', $value);
        });
        break;
    }
  }

  protected function applyCustomFieldRule(string $field, string $operator, $value): void
  {
    $this->query->whereHas('customFields', function (Builder $query) use ($field, $operator, $value) {
      $query->where('key', $field)
        ->where('value', $this->convertOperator($operator), $value);
    });
  }

  protected function applyDateRule(string $field, string $operator, $value): void
  {
    $date = $this->parseDateValue($value);
    $this->query->where($field, $this->convertOperator($operator), $date);
  }

  protected function convertOperator(string $operator): string
  {
    return match ($operator) {
      'equals' => '=',
      'not_equals' => '!=',
      'greater_than' => '>',
      'less_than' => '<',
      'greater_than_or_equal' => '>=',
      'less_than_or_equal' => '<=',
      default => $operator,
    };
  }

  protected function parseDateValue($value): Carbon
  {
    if (is_numeric($value)) {
      return Carbon::now()->subDays($value);
    }
    return Carbon::parse($value);
  }
}
