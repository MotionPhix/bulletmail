<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MailingListApiController extends Controller
{
  use AuthorizesRequests;

  /**
   * Get all mailing lists for the current team
   */
  public function index(Request $request): JsonResponse
  {
    $lists = MailingList::query()
      ->forTeam($request->user()->currentTeam)
      ->when($request->search, function ($query, $search) {
        $query->where('name', 'like', "%{$search}%");
      })
      ->withCount('subscribers')
      ->latest()
      ->get()
      ->map(fn ($list) => [
        'id' => $list->id,
        'name' => $list->name,
        'description' => $list->description,
        'subscriberCount' => $list->subscribers_count,
        'is_default' => $list->is_default,
        'created_at' => $list->created_at
      ]);

    return response()->json([
      'lists' => $lists
    ]);
  }

  /**
   * Get a specific mailing list with its subscribers count
   */
  public function show(Request $request, MailingList $mailingList): JsonResponse
  {
    $this->authorize('view', $mailingList);

    return response()->json([
      'list' => [
        'id' => $mailingList->id,
        'name' => $mailingList->name,
        'description' => $mailingList->description,
        'subscriberCount' => $mailingList->subscribers()->count(),
        'is_default' => $mailingList->is_default,
        'created_at' => $mailingList->created_at,
        'updated_at' => $mailingList->updated_at
      ]
    ]);
  }

  /**
   * Get subscribers for a specific mailing list
   */
  public function subscribers(Request $request, MailingList $mailingList): JsonResponse
  {
    $this->authorize('view', $mailingList);

    $subscribers = $mailingList->subscribers()
      ->select(['id', 'email', 'first_name', 'last_name', 'created_at'])
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('email', 'like', "%{$search}%")
            ->orWhere('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%");
        });
      })
      ->latest()
      ->paginate($request->per_page ?? 10);

    return response()->json([
      'subscribers' => $subscribers,
      'total' => $subscribers->total()
    ]);
  }
}
