<?php

namespace App\Jobs;

use App\Models\MailingList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SynchronizeMailingList implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MailingList $list;

    public function __construct(MailingList $list)
    {
        $this->list = $list;
    }

    public function handle(): void
    {
        $team = $this->list->team;
        $rules = $this->list->segment_rules;

        if (!$rules) {
            return;
        }

        DB::transaction(function () use ($team, $rules) {
            // Get matching subscribers based on segment rules
            $query = $team->subscribers()->query();

            foreach ($rules as $rule) {
                $this->applyRule($query, $rule);
            }

            $subscriberIds = $query->pluck('id')->toArray();

            // Sync subscribers with the list
            $this->list->subscribers()->sync($subscriberIds);

            // Update subscriber count
            $this->list->update([
                'subscriber_count' => count($subscriberIds),
                'last_synced_at' => now()
            ]);
        });
    }

    protected function applyRule($query, array $rule): void
    {
        $method = $rule['match'] === 'any' ? 'orWhere' : 'where';

        $query->$method(function ($q) use ($rule) {
            foreach ($rule['conditions'] as $condition) {
                $this->applyCondition($q, $condition);
            }
        });
    }

    protected function applyCondition($query, array $condition): void
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        switch ($operator) {
            case 'equals':
                $query->where($field, '=', $value);
                break;
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
            case 'contains':
                $query->where($field, 'LIKE', "%{$value}%");
                break;
            case 'not_contains':
                $query->where($field, 'NOT LIKE', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($field, 'LIKE', "{$value}%");
                break;
            case 'ends_with':
                $query->where($field, 'LIKE', "%{$value}");
                break;
            case 'is_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNull($field)->orWhere($field, '');
                });
                break;
            case 'is_not_empty':
                $query->where(function ($q) use ($field) {
                    $q->whereNotNull($field)->where($field, '!=', '');
                });
                break;
            case 'in_list':
                $query->whereIn($field, (array)$value);
                break;
            case 'not_in_list':
                $query->whereNotIn($field, (array)$value);
                break;
        }
    }
}
