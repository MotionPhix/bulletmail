<?php

namespace App\Listeners;

use App\Events\TeamInvitationAccepted;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleTeamInvitationAccepted implements ShouldQueue
{
  /**
   * Handle the event.
   */
  public function handle(TeamInvitationAccepted $event): void
  {
    // Set up user's initial settings for this team
    $event->user->settings()->create([
      'team_id' => $event->team->id,
      'preferences' => [
        'notifications' => [
          'email' => true,
          'web' => true
        ],
        'display' => [
          'theme' => 'system',
          'layout' => 'default'
        ]
      ]
    ]);

    // Send welcome notification
    $event->user->notify(new \App\Notifications\WelcomeToTeam($event->team));
  }
}
