<?php

namespace App\Models\Concerns;

use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasNotificationPreferences
{
  public function notificationPreferences(): MorphMany
  {
    return $this->morphMany(NotificationPreference::class, 'preferenceable');
  }

  public function getNotificationPreference(string $type): NotificationPreference
  {
    return $this->notificationPreferences()
      ->firstOrCreate(
        ['type' => $type],
        [
          'channels' => ['web', 'email'],
          'enabled' => true
        ]
      );
  }

  public function updateNotificationPreference(string $type, array $data): NotificationPreference
  {
    $preference = $this->getNotificationPreference($type);
    $preference->update($data);
    return $preference;
  }
}
