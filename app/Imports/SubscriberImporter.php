<?php

namespace App\Imports;

use App\Models\{Team, MailingList};
use App\Enums\SubscriberStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\{
  ToCollection,
  WithHeadingRow,
  WithValidation,
};

class SubscriberImporter implements ToCollection, WithHeadingRow, WithValidation
{
  protected Team $team;
  protected array $options;
  protected array $results;
  protected ?MailingList $defaultList;
  protected array $mappedFields;

  public function __construct(Team $team, array $options = [])
  {
    $this->team = $team;
    $this->options = $options;
    $this->results = [
      'total' => 0,
      'imported' => 0,
      'updated' => 0,
      'failed' => 0,
      'errors' => []
    ];
    $this->defaultList = isset($options['list_id'])
      ? MailingList::find($options['list_id'])
      : null;
    $this->mappedFields = $options['field_mapping'] ?? [];
  }

  public function collection(Collection $rows)
  {
    $rows->each(function ($row) {
      $this->results['total']++;

      try {
        $data = $this->mapFields($row);
        $this->processRow($data);
      } catch (\Exception $e) {
        $this->handleError($row, $e->getMessage());
      }
    });
  }

  protected function mapFields($row): array
  {
    $data = [];
    foreach ($this->mappedFields as $target => $source) {
      $data[$target] = $row[$source] ?? null;
    }

    // Handle custom fields
    $customFields = [];
    foreach ($row as $key => $value) {
      if (!in_array($key, array_values($this->mappedFields))) {
        $customFields[$key] = $value;
      }
    }

    if (!empty($customFields)) {
      $data['custom_fields'] = $customFields;
    }

    return $data;
  }

  protected function processRow(array $data)
  {
    $subscriber = $this->team->subscribers()
      ->where('email', $data['email'])
      ->first();

    if ($subscriber) {
      if ($this->options['update_existing'] ?? true) {
        $subscriber->update($data);
        $this->results['updated']++;
      }
    } else {
      $subscriber = $this->team->subscribers()->create(array_merge($data, [
        'user_id' => auth()->id(),
        'status' => SubscriberStatus::SUBSCRIBED,
        'subscribed_at' => now(),
        'source' => 'import'
      ]));
      $this->results['imported']++;
    }

    // Add to default list if specified
    if ($this->defaultList) {
      $subscriber->addToList($this->defaultList);
    }
  }

  protected function handleError($row, string $message)
  {
    $this->results['failed']++;
    $this->results['errors'][] = [
      'row' => $this->results['total'] + 1,
      'data' => $row->toArray(),
      'message' => $message
    ];
  }

  public function rules(): array
  {
    return [
      'email' => [
        'required',
        'email',
        Rule::unique('subscribers', 'email')
          ->where('team_id', $this->team->id)
          ->ignore(null, 'id')
      ],
      'first_name' => ['sometimes', 'string', 'max:255'],
      'last_name' => ['sometimes', 'string', 'max:255'],
    ];
  }

  public function getResults(): array
  {
    return $this->results;
  }
}
