<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
  public function index(Request $request): JsonResponse
  {
    $notifications = $request->user()
      ->notifications()
      ->with(['team'])
      ->latest()
      ->paginate(15);

    return response()->json($notifications);
  }

  public function unread(Request $request): JsonResponse
  {
    $notifications = $request->user()
      ->unreadNotifications()
      ->with(['team'])
      ->latest()
      ->get();

    return response()->json([
      'notifications' => $notifications,
      'count' => $notifications->count()
    ]);
  }

  public function markAsRead(Request $request, string $id): JsonResponse
  {
    $notification = $request->user()
      ->notifications()
      ->findOrFail($id);

    $notification->markAsRead();

    return response()->json(['success' => true]);
  }

  public function markAllAsRead(Request $request): JsonResponse
  {
    $request->user()->unreadNotifications->markAsRead();

    return response()->json(['success' => true]);
  }
}
