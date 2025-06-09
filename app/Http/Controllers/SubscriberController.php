<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriberRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\{Subscriber, MailingList};
use App\Services\SubscriberService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\SubscribersExport;
use Maatwebsite\Excel\Facades\Excel;

class SubscriberController extends Controller
{
  use AuthorizesRequests;

  protected SubscriberService $subscriberService;

  public function __construct(SubscriberService $subscriberService)
  {
    $this->subscriberService = $subscriberService;
  }

  public function index(Request $request)
  {
    $filters = $request->only(['search', 'status', 'list_id', 'segment_id', 'sort', 'direction']);
    $subscribers = $this->subscriberService->getSubscribers(
      $request->user()->currentTeam,
      $filters,
      $request->integer('per_page', 10)
    );

    return Inertia::render('subscribers/Index', [
      'subscribers' => $subscribers,
      'filters' => $filters,
      'stats' => $this->subscriberService->getEngagementStats($request->user()->currentTeam),
      'lists' => $request->user()->currentTeam->mailingLists()
        ->select('id', 'name', 'subscriber_count')
        ->get(),
      'segments' => $request->user()->currentTeam->segments()
        ->select('id', 'name', 'subscriber_count')
        ->get()
    ]);
  }

  public function store(SubscriberRequest $request)
  {
    $subscriber = $this->subscriberService->create(
      $request->user()->currentTeam,
      $request->validated()
    );

    return back()->with('success', 'Subscriber added successfully.');
  }

  public function create(Request $request)
  {
    $subscriber = new Subscriber([
      'team_id' => $request->user()->currentTeam->id,
      'user_id' => $request->user()->id
    ]);

    // Ensure the user has permission to create subscribers
    $this->authorize('create', $subscriber);

    return Inertia::render('subscribers/Create', [
      'subscriber' => $subscriber
    ]);
  }

  public function show(Subscriber $subscriber)
  {
    $this->authorize('view', $subscriber);

    $details = $this->subscriberService->getSubscriberDetails($subscriber);

    return Inertia::render('subscribers/Show', [
      'subscriber' => $details
    ]);
  }

  public function update(SubscriberRequest $request, Subscriber $subscriber)
  {
    $this->authorize('update', $subscriber);

    $this->subscriberService->update($subscriber, $request->validated());

    return back()->with('success', 'Subscriber updated successfully.');
  }

  public function destroy(Subscriber $subscriber)
  {
    $this->authorize('delete', $subscriber);

    $this->subscriberService->delete($subscriber);

    return back()->with('success', 'Subscriber deleted successfully.');
  }

  public function bulkAction(Request $request)
  {
    $this->validate($request, [
      'action' => ['required', 'string'],
      'ids' => ['required', 'array'],
      'ids.*' => ['exists:subscribers,id'],
      'list_id' => ['sometimes', 'exists:mailing_lists,id'],
      'status' => ['sometimes', 'string']
    ]);

    $subscribers = Subscriber::whereIn('id', $request->ids)
      ->where('team_id', $request->user()->currentTeam->id)
      ->get();

    $result = match ($request->action) {
      'delete' => $this->subscriberService->bulkDelete($subscribers),
      'update_status' => $this->subscriberService->bulkUpdate($subscribers, ['status' => $request->status]),
      'add_to_list' => $this->subscriberService->bulkAddToList($subscribers, MailingList::find($request->list_id)),
      'remove_from_list' => $this->subscriberService->bulkRemoveFromList($subscribers, MailingList::find($request->list_id)),
      default => throw new \InvalidArgumentException('Invalid bulk action')
    };

    return back()->with('success', $result['message']);
  }

  public function import(Request $request)
  {
    $this->validate($request, [
      'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
      'options' => ['sometimes', 'array']
    ]);

    $result = $this->subscriberService->import(
      $request->user()->currentTeam,
      $request->file('file'),
      $request->input('options', [])
    );

    if (!empty($result['errors'])) {
      return back()->with([
        'warning' => $result['message'],
        'import_errors' => $result['errors']
      ]);
    }

    return back()->with('success', $result['message']);
  }

  public function export(Request $request)
  {
    $this->validate($request, [
      'format' => ['required', 'in:csv,xlsx'],
      'filters' => ['sometimes', 'array']
    ]);

    return Excel::download(
      new SubscribersExport(
        $request->user()->currentTeam,
        $request->input('filters', [])
      ),
      'subscribers-' . now()->format('Y-m-d') . '.' . $request->format
    );
  }

  public function unsubscribe(Request $request, string $uuid)
  {
    $subscriber = Subscriber::whereUuid($uuid)->firstOrFail();

    $this->subscriberService->unsubscribe(
      $subscriber,
      $request->input('reason'),
      $request->input('campaign_id')
    );

    return Inertia::render('Unsubscribe/Success', [
      'message' => 'You have been successfully unsubscribed.'
    ]);
  }

  public function preferences(Request $request, string $uuid)
  {
    $subscriber = Subscriber::whereUuid($uuid)->firstOrFail();

    return Inertia::render('Subscribers/Preferences', [
      'subscriber' => $subscriber->only('uuid', 'email', 'first_name', 'last_name'),
      'lists' => $subscriber->team->mailingLists()
        ->with(['subscribers' => function ($query) use ($subscriber) {
          $query->where('subscriber_id', $subscriber->id);
        }])
        ->get()
        ->map(fn($list) => [
          'id' => $list->id,
          'name' => $list->name,
          'description' => $list->description,
          'subscribed' => $list->subscribers->isNotEmpty()
        ])
    ]);
  }

  public function updatePreferences(Request $request, string $uuid)
  {
    $subscriber = Subscriber::whereUuid($uuid)->firstOrFail();

    $this->validate($request, [
      'lists' => ['required', 'array'],
      'lists.*' => ['exists:mailing_lists,id']
    ]);

    $this->subscriberService->updateSubscriberLists(
      $subscriber,
      $request->lists
    );

    return back()->with('success', 'Your preferences have been updated.');
  }
}
