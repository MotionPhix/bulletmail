<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SegmentController extends Controller
{
  protected SegmentService $segmentService;

  public function __construct(SegmentService $segmentService)
  {
    $this->segmentService = $segmentService;
  }

  public function index(Request $request)
  {
    $segments = $request->user()->currentTeam->segments()
      ->withCount('subscribers')
      ->latest()
      ->paginate();

    return Inertia::render('segments/Index', [
      'segments' => $segments
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'conditions' => ['required', 'array'],
      'conditions.*.match' => ['required', 'in:any,all'],
      'conditions.*.conditions' => ['required', 'array'],
      'conditions.*.conditions.*.field' => ['required', 'string'],
      'conditions.*.conditions.*.operator' => ['required', 'string'],
      'conditions.*.conditions.*.value' => ['required'],
    ]);

    $segment = $this->segmentService->create(
      $request->user()->currentTeam,
      $validated
    );

    return back()->with('success', 'Segment created successfully.');
  }

  public function show(Segment $segment)
  {
    $this->authorize('view', $segment);

    return Inertia::render('segments/Show', [
      'segment' => [
        'id' => $segment->id,
        'uuid' => $segment->uuid,
        'name' => $segment->name,
        'description' => $segment->description,
        'conditions' => $segment->conditions,
        'created_at' => $segment->created_at->format('Y-m-d H:i:s')
      ],
      'subscribers' => $this->segmentService->getSubscribers($segment, [
        'sort' => request('sort', 'created_at'),
        'direction' => request('direction', 'desc'),
        'per_page' => request('per_page', 10)
      ]),
      'stats' => $this->segmentService->getStats($segment)
    ]);
  }

  public function update(Request $request, Segment $segment)
  {
    $this->authorize('update', $segment);

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'conditions' => ['required', 'array'],
      'conditions.*.match' => ['required', 'in:any,all'],
      'conditions.*.conditions' => ['required', 'array'],
      'conditions.*.conditions.*.field' => ['required', 'string'],
      'conditions.*.conditions.*.operator' => ['required', 'string'],
      'conditions.*.conditions.*.value' => ['required'],
    ]);

    $this->segmentService->update($segment, $validated);

    return back()->with('success', 'Segment updated successfully.');
  }

  public function destroy(Segment $segment)
  {
    $this->authorize('delete', $segment);

    $this->segmentService->delete($segment);

    return back()->with('success', 'Segment deleted successfully.');
  }

  public function duplicate(Segment $segment)
  {
    $this->authorize('create', Segment::class);

    $newSegment = $this->segmentService->duplicate($segment);

    return redirect()->route('segments.show', $newSegment)
      ->with('success', 'Segment duplicated successfully.');
  }

  public function preview(Request $request, Segment $segment)
  {
    $this->authorize('view', $segment);

    $matching = $segment->previewSubscribers(10);
    $total = $segment->getMatchingSubscribersCount();

    return response()->json([
      'subscribers' => $matching->map(fn($subscriber) => [
        'id' => $subscriber->id,
        'uuid' => $subscriber->uuid,
        'email' => $subscriber->email,
        'first_name' => $subscriber->first_name,
        'last_name' => $subscriber->last_name,
        'status' => $subscriber->status->value,
        'created_at' => $subscriber->created_at->format('Y-m-d H:i:s')
      ]),
      'total' => $total
    ]);
  }
}
