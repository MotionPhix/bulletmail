<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SegmentApiController extends Controller
{
  use AuthorizesRequests;

  /**
   * Get all segments for the current team
   */
  public function index(Request $request): JsonResponse
  {
    $segments = Segment::query()
      ->forTeam($request->user()->currentTeam)
      ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', "%{$search}%");
      })
      ->latest()
      ->get()
      ->map(fn ($segment) => [
        'id' => $segment->id,
        'name' => $segment->name,
        'description' => $segment->description,
        'subscriberCount' => $segment->subscriber_count,
        'created_at' => $segment->created_at
      ]);

    return response()->json([
      'segments' => $segments
    ]);
  }

  /**
   * Get a specific segment with its subscribers count
   */
  public function show(Request $request, Segment $segment): JsonResponse
  {
    $this->authorize('view', $segment);

    return response()->json([
      'segment' => [
        'id' => $segment->id,
        'name' => $segment->name,
        'description' => $segment->description,
        'conditions' => $segment->conditions,
        'subscriberCount' => $segment->subscriber_count,
        'created_at' => $segment->created_at,
        'updated_at' => $segment->updated_at
      ]
    ]);
  }

  /**
   * Get segment preview data (subscribers matching the segment conditions)
   */
  public function preview(Request $request, Segment $segment): JsonResponse
  {
    $this->authorize('view', $segment);

    $subscribers = $segment->getSubscribersQuery()
      ->select(['id', 'email', 'first_name', 'last_name', 'created_at'])
      ->latest()
      ->paginate($request->per_page ?? 10);

    return response()->json([
      'subscribers' => $subscribers,
      'total' => $subscribers->total()
    ]);
  }
}
