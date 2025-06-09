<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriberImportCompleted extends Notification
{
  use Queueable;

  protected array $results;
  protected string $teamName;

  public function __construct(array $results, string $teamName)
  {
    $this->results = $results;
    $this->teamName = $teamName;
  }

  public function via($notifiable): array
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject("Subscriber Import Completed - {$this->teamName}")
      ->greeting('Import Complete!')
      ->line('Your subscriber import has been completed.')
      ->line("Successfully imported: {$this->results['imported']}")
      ->line("Updated existing: {$this->results['updated']}")
      ->lineIf($this->results['failed'] > 0, "Failed imports: {$this->results['failed']}")
      ->action('View Subscribers', route('subscribers.index'))
      ->line('Thank you for using our application!');
  }

  public function toArray($notifiable): array
  {
    return [
      'title' => 'Subscriber Import Completed',
      'message' => "Import completed with {$this->results['imported']} new subscribers",
      'results' => $this->results,
      'team_name' => $this->teamName
    ];
  }
}
