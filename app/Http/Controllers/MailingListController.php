<?php

namespace App\Http\Controllers;

use App\Models\MailingList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\MailingListService;

class MailingListController extends Controller
{
  use AuthorizesRequests;
  protected MailingListService $mailingListService;

  public function __construct(MailingListService $mailingListService)
  {
    $this->mailingListService = $mailingListService;
  }

  public function index(Request $request)
  {
    $lists = $request->user()->currentTeam->mailingLists()
      ->withCount('subscribers')
      ->latest()
      ->paginate();

    return Inertia::render('Lists/Index', [
      'lists' => $lists
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'double_opt_in' => ['boolean'],
      'welcome_email_id' => ['nullable', 'exists:campaigns,id'],
    ]);

    $list = $this->mailingListService->create(
      $request->user()->currentTeam,
      $validated
    );

    return back()->with('success', 'Mailing list created successfully.');
  }

  public function show(MailingList $list)
  {
    $this->authorize('view', $list);

    $subscribers = $list->subscribers()
      ->latest()
      ->paginate();

    return Inertia::render('Lists/Show', [
      'list' => $list,
      'subscribers' => $subscribers,
      'stats' => $this->mailingListService->getStats($list)
    ]);
  }

  public function update(Request $request, MailingList $list)
  {
    $this->authorize('update', $list);

    $validated = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'description' => ['nullable', 'string'],
      'double_opt_in' => ['boolean'],
      'welcome_email_id' => ['nullable', 'exists:campaigns,id'],
      'settings' => ['nullable', 'array']
    ]);

    $this->mailingListService->update($list, $validated);

    return back()->with('success', 'Mailing list updated successfully.');
  }

  public function destroy(MailingList $list)
  {
    $this->authorize('delete', $list);

    $this->mailingListService->delete($list);

    return back()->with('success', 'Mailing list deleted successfully.');
  }

  public function synchronize(MailingList $list)
  {
    $this->authorize('update', $list);

    $this->mailingListService->synchronize($list);

    return back()->with('success', 'List synchronization started.');
  }

  public function duplicate(MailingList $list)
  {
    $this->authorize('create', MailingList::class);

    $newList = $this->mailingListService->duplicate($list);

    return redirect()->route('lists.show', $newList)
      ->with('success', 'List duplicated successfully.');
  }

  public function export(MailingList $list)
  {
    $this->authorize('view', $list);

    return $this->mailingListService->export($list);
  }

  public function addSubscribers(Request $request, MailingList $list)
  {
    $this->authorize('update', $list);

    $validated = $request->validate([
      'subscriber_ids' => ['required', 'array'],
      'subscriber_ids.*' => ['exists:subscribers,id']
    ]);

    $this->mailingListService->addSubscribers($list, $validated['subscriber_ids']);

    return back()->with('success', 'Subscribers added to list successfully.');
  }

  public function removeSubscribers(Request $request, MailingList $list)
  {
    $this->authorize('update', $list);

    $validated = $request->validate([
      'subscriber_ids' => ['required', 'array'],
      'subscriber_ids.*' => ['exists:subscribers,id']
    ]);

    $this->mailingListService->removeSubscribers($list, $validated['subscriber_ids']);

    return back()->with('success', 'Subscribers removed from list successfully.');
  }
}
