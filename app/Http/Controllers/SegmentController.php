<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SegmentController extends Controller
{
  use AuthorizesRequests;

  public function index(Request $request)
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
        'conditions' => $segment->conditions,
        'subscriber_count' => Cache::remember(
          "segment_{$segment->id}_count",
          now()->addHours(1),
          fn () => $segment->getSubscribersQuery()->count()
        ),
        'created_at' => $segment->created_at,
        'updated_at' => $segment->updated_at,
      ]);

    return Inertia::render('Segments/Index', [
      'segments' => $segments,
      'filters' => $request->only(['search']),
    ]);
  }

  public function create()
  {
    return Inertia::render('Segments/Create', [
      'conditions' => $this->getAvailableConditions(),
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'conditions' => ['required', 'array', 'min:1'],
      'conditions.*.field' => ['required', 'string'],
      'conditions.*.operator' => ['required', 'string'],
      'conditions.*.value' => ['required'],
    ]);

    $segment = $request->user()->currentTeam->segments()->create($validated);

    return redirect()->route('segments.edit', $segment)
      ->with('success', 'Segment created successfully.');
  }

  public function edit(Segment $segment)
  {
    $this->authorize('update', $segment);

    return Inertia::render('Segments/Edit', [
      'segment' => [
        'id' => $segment->id,
        'name' => $segment->name,
        'description' => $segment->description,
        'conditions' => $segment->conditions,
      ],
      'conditions' => $this->getAvailableConditions(),
    ]);
  }

  public function update(Request $request, Segment $segment)
  {
    $this->authorize('update', $segment);

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'conditions' => ['required', 'array', 'min:1'],
      'conditions.*.field' => ['required', 'string'],
      'conditions.*.operator' => ['required', 'string'],
      'conditions.*.value' => ['required'],
    ]);

    $segment->update($validated);

    // Clear the cached subscriber count
    Cache::forget("segment_{$segment->id}_count");

    return back()->with('success', 'Segment updated successfully.');
  }

  public function destroy(Segment $segment)
  {
    $this->authorize('delete', $segment);

    $segment->delete();

    return redirect()->route('segments.index')
      ->with('success', 'Segment deleted successfully.');
  }

  public function preview(Request $request, Segment $segment)
  {
    $this->authorize('view', $segment);

    $subscribers = $segment->getSubscribersQuery()
      ->select(['id', 'email', 'first_name', 'last_name', 'created_at'])
      ->latest()
      ->paginate($request->per_page ?? 10);

    return back()->with('preview', [
      'subscribers' => $subscribers,
      'total' => $subscribers->total(),
    ]);
  }

  public function duplicate(Segment $segment)
  {
    $this->authorize('create', Segment::class);

    $newSegment = $segment->replicate();
    $newSegment->name = "Copy of {$segment->name}";
    $newSegment->save();

    return redirect()->route('segments.edit', $newSegment)
      ->with('success', 'Segment duplicated successfully.');
  }

  public function calculateSize(Segment $segment)
  {
    $this->authorize('view', $segment);

    $count = $segment->getSubscribersQuery()->count();

    // Update the cache
    Cache::put("segment_{$segment->id}_count", $count, now()->addHours(1));

    return back()->with('segment_size', $count);
  }

  public function bulkDestroy(Request $request)
  {
    $validated = $request->validate([
      'segments' => ['required', 'array'],
      'segments.*' => ['required', 'exists:segments,id'],
    ]);

    $segments = Segment::whereIn('id', $validated['segments'])
      ->forTeam($request->user()->currentTeam)
      ->get();

    foreach ($segments as $segment) {
      $this->authorize('delete', $segment);
      $segment->delete();
    }

    return back()->with('success', 'Selected segments deleted successfully.');
  }

  private function getAvailableConditions(): array
  {
    return [
      'fields' => [
        [
          'value' => 'email',
          'label' => 'Email Address',
          'operators' => ['equals', 'contains', 'starts_with', 'ends_with'],
        ],
        [
          'value' => 'first_name',
          'label' => 'First Name',
          'operators' => ['equals', 'contains', 'starts_with', 'ends_with'],
        ],
        [
          'value' => 'last_name',
          'label' => 'Last Name',
          'operators' => ['equals', 'contains', 'starts_with', 'ends_with'],
        ],
        [
          'value' => 'created_at',
          'label' => 'Subscription Date',
          'operators' => ['before', 'after', 'between'],
        ],
        [
          'value' => 'status',
          'label' => 'Status',
          'operators' => ['equals'],
          'values' => [
            ['value' => 'subscribed', 'label' => 'Subscribed'],
            ['value' => 'unsubscribed', 'label' => 'Unsubscribed'],
            ['value' => 'bounced', 'label' => 'Bounced'],
            ['value' => 'complained', 'label' => 'Complained'],
          ],
        ],
      ],
      'operators' => [
        ['value' => 'equals', 'label' => 'Equals'],
        ['value' => 'contains', 'label' => 'Contains'],
        ['value' => 'starts_with', 'label' => 'Starts with'],
        ['value' => 'ends_with', 'label' => 'Ends with'],
        ['value' => 'before', 'label' => 'Before'],
        ['value' => 'after', 'label' => 'After'],
        ['value' => 'between', 'label' => 'Between'],
      ],
    ];
  }
}
