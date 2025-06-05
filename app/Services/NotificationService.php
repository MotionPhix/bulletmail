<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\Notification;
use App\Enums\NotificationType;
use App\Mail\Notifications\CampaignSentNotification;
use App\Mail\Notifications\QuotaWarningNotification;
use App\Mail\Notifications\NewSubscriberNotification;
use App\Mail\Notifications\TemplateCreatedNotification;
use Illuminate\Support\Facades\Mail;
use Pusher\Pusher;

class NotificationService
{
  protected Pusher $pusher;

  public function __construct()
  {
    $this->pusher = new Pusher(
      config('broadcasting.connections.pusher.key'),
      config('broadcasting.connections.pusher.secret'),
      config('broadcasting.connections.pusher.app_id'),
      config('broadcasting.connections.pusher.options')
    );
  }

  protected function sendEmailNotification(
    Team $team,
    NotificationType $type,
    array $data,
    ?User $user = null
  ): void {
    $preference = $user
      ? $user->getNotificationPreference($type->value)
      : $team->getNotificationPreference($type->value);

    if (!$preference->enabled || !in_array('email', $preference->channels)) {
      return;
    }

    $recipient = $user ? $user->email : $team->owner->email;

    $mailable = match ($type) {
      NotificationType::CAMPAIGN_SENT => new CampaignSentNotification(
        $data['campaign'],
        $team
      ),
      NotificationType::QUOTA_WARNING => new QuotaWarningNotification(
        $team,
        $data['quota']
      ),
      NotificationType::NEW_SUBSCRIBER => new NewSubscriberNotification(
        $team,
        $data['subscriber']
      ),
      NotificationType::TEMPLATE_CREATED => new TemplateCreatedNotification(
        $team,
        $data['template']
      ),
      default => null
    };

    if ($mailable) {
      Mail::to($recipient)->queue($mailable);
    }
  }

  public function send(
    Team $team,
    NotificationType $type,
    string $message,
    array $data = [],
    ?User $user = null
  ): void {
    // Create notification record
    $notification = Notification::create([
      'team_id' => $team->id,
      'type' => $type,
      'message' => $message,
      'data' => $data,
      'notifiable_type' => $user ? User::class : Team::class,
      'notifiable_id' => $user ? $user->id : $team->id
    ]);

    // Send real-time notification
    $this->sendPusherNotification($team, $notification);

    // Send email notification if enabled
    $this->sendEmailNotification($team, $type, $data, $user);
  }

  protected function sendPusherNotification(Team $team, Notification $notification): void
  {
    // Broadcast to team channel
    $this->pusher->trigger(
      "team.{$team->id}",
      'notification',
      [
        'id' => $notification->id,
        'type' => $notification->type->value,
        'message' => $notification->message,
        'icon' => $notification->type->getIcon(),
        'color' => $notification->type->getColor(),
        'data' => $notification->data,
        'created_at' => $notification->created_at
      ]
    );
  }
}
