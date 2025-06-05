<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationPreferenceController extends Controller
{
  public function getUserPreferences(Request $request, User $user): JsonResponse
  {
    $this->authorize('view', $user);

    $preferences = $user->notificationPreferences
      ->keyBy('type')
      ->toArray();

    return response()->json($preferences);
  }

  public function getTeamPreferences(Request $request, Team $team): JsonResponse
  {
    $this->authorize('view', $team);

    $preferences = $team->notificationPreferences
      ->keyBy('type')
      ->toArray();

    return response()->json($preferences);
  }

  public function updateUserPreference(Request $request, User $user, string $type): JsonResponse
  {
    $this->authorize('update', $user);

    $data = $request->validate([
      'enabled' => 'boolean',
      'channels' => 'array'
    ]);

    $preference = $user->updateNotificationPreference($type, $data);

    return response()->json($preference);
  }

  public function updateTeamPreference(Request $request, Team $team, string $type): JsonResponse
  {
    $this->authorize('update', $team);

    $data = $request->validate([
      'enabled' => 'boolean',
      'channels' => 'array'
    ]);

    $preference = $team->updateNotificationPreference($type, $data);

    return response()->json($preference);
  }
}
