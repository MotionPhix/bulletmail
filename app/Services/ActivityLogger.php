<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Facades\ActivityLogger as Logger;

class ActivityLogger
{
  /**
   * Log an activity.
   */
  public static function log(
    Model $performedOn,
    Model $causedBy,
    string $description,
    array $properties = []
  ): void {
    Logger::performedOn($performedOn)
      ->causedBy($causedBy)
      ->withProperties($properties)
      ->log($description);
  }

  /**
   * Log an invitation activity.
   */
  public static function logInvitation(
    Model $invitation,
    string $description,
    array $extraProperties = []
  ): void {
    $properties = array_merge([
      'team' => $invitation->team->name,
      'email' => $invitation->email,
      'role' => $invitation->role,
      'status' => $invitation->status,
      'expires_at' => $invitation->expires_at?->toDateTimeString(),
    ], $extraProperties);

    self::log(
      $invitation,
      $invitation->inviter,
      $description,
      $properties
    );
  }
}
