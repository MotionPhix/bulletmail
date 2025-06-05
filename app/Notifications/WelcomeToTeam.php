<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToTeam extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Create a new notification instance.
   */
  public function __construct(
    protected readonly Team $team
  ) {}

  /**
   * Get the notification's delivery channels.
   *
   * @return array<int, string>
   */
  public function via(object $notifiable): array
  {
    return ['mail', 'database'];
  }

  /**
   * Get the mail representation of the notification.
   */
  public function toMail(object $notifiable): MailMessage
  {
    return (new MailMessage)
      ->subject("Welcome to {$this->team->name}")
      ->greeting("Welcome {$notifiable->first_name}!")
      ->line("You've successfully joined {$this->team->name} on " . config('app.name'))
      ->line('Here are a few things you can do to get started:')
      ->line('• Complete your profile settings')
      ->line('• Review team email templates')
      ->line('• Check your sending quotas')
      ->action('Go to Dashboard', route('dashboard'))
      ->line('If you have any questions, feel free to reach out to your team administrator.');
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toDatabase(object $notifiable): array
  {
    return [
      'team_id' => $this->team->id,
      'team_name' => $this->team->name,
      'message' => "Welcome to {$this->team->name}! Let's get you started.",
      'action_url' => route('dashboard'),
      'action_text' => 'Go to Dashboard',
      'type' => 'team.welcome'
    ];
  }

  /**
   * Get the array representation of the notification.
   *
   * @return array<string, mixed>
   */
  public function toArray(object $notifiable): array
  {
    return [
      'team_id' => $this->team->id,
      'team_name' => $this->team->name,
      'type' => 'team.welcome',
      'action' => [
        'url' => route('dashboard'),
        'text' => 'Go to Dashboard'
      ]
    ];
  }
}
