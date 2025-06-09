<?php

namespace App\Exports;

use App\Models\MailingList;
use Maatwebsite\Excel\Concerns\{
  FromQuery,
  Exportable,
  WithMapping,
  WithHeadings,
  WithStyles
};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListSubscribersExport implements FromQuery, WithMapping, WithHeadings, WithStyles
{
  use Exportable;

  protected MailingList $list;
  protected array $customFields;

  public function __construct(MailingList $list)
  {
    $this->list = $list;
    $this->customFields = $this->getCustomFields();
  }

  public function query()
  {
    return $this->list->subscribers()
      ->select('subscribers.*', 'mailing_list_subscriber.created_at as joined_at')
      ->orderBy('mailing_list_subscriber.created_at', 'desc');
  }

  public function map($subscriber): array
  {
    $data = [
      $subscriber->email,
      $subscriber->first_name,
      $subscriber->last_name,
      $subscriber->status->value,
      $subscriber->joined_at->format('Y-m-d H:i:s'),
      $subscriber->engagement_score,
      $subscriber->average_open_rate,
      $subscriber->average_click_rate,
      $subscriber->emails_received,
      $subscriber->emails_opened,
      $subscriber->emails_clicked,
      $subscriber->source,
      $subscriber->created_at->format('Y-m-d H:i:s')
    ];

    foreach ($this->customFields as $field) {
      $data[] = $subscriber->getCustomField($field);
    }

    return $data;
  }

  public function headings(): array
  {
    $headers = [
      'Email Address',
      'First Name',
      'Last Name',
      'Status',
      'Joined List',
      'Engagement Score',
      'Open Rate %',
      'Click Rate %',
      'Emails Received',
      'Emails Opened',
      'Emails Clicked',
      'Source',
      'Created Date'
    ];

    return array_merge($headers, array_map('ucwords', $this->customFields));
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
      'A1:M1' => ['fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'F3F4F6']
      ]]
    ];
  }

  protected function getCustomFields(): array
  {
    return $this->list->subscribers()
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
