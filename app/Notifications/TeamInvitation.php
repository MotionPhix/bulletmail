<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    protected User $inviter,
    protected Team $team
  ) {}

  public function via($notifiable): array
  {
    return ['mail'];
  }

  public function toMail($notifiable): MailMessage
  {
    $acceptUrl = route('team-invitations.accept', [
      'token' => $notifiable->invitation_token
    ]);

    return (new MailMessage)
      ->subject('You\'ve been invited to join ' . $this->team->name)
      ->view('emails.team.invitation', [
        'inviter' => $this->inviter,
        'team' => $this->team,
        'acceptUrl' => $acceptUrl,
        'notifiable' => $notifiable
      ]);
  }
}
