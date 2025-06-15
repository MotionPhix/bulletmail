<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\MergeTag;
use App\Services\CampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CampaignController extends Controller
{
  use AuthorizesRequests;

  public function __construct(
    protected CampaignService $campaignService
  ) {}

  public function index(Request $request)
  {
    $query = Campaign::query()
      ->where('team_id', auth()->user()->current_team_id)
      ->select(['id', 'uuid', 'name', 'subject', 'status', 'scheduled_at', 'created_at'])
      ->with(['stats', 'user:id,name,email']);

    if ($search = $request->input('search')) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('subject', 'like', "%{$search}%")
          ->orWhere('status', 'like', "%{$search}%");
      });
    }

    if ($status = $request->input('status')) {
      $query->where('status', $status);
    }

    $sortField = $request->input('sort', 'name');
    $sortDirection = $request->input('direction', 'desc');

    $allowedSortFields = ['name', 'status', 'scheduled_at', 'created_at'];

    if (in_array($sortField, $allowedSortFields)) {
      $query->orderBy($sortField, $sortDirection);
    }

    return Inertia::render('campaigns/Index', [
      'campaigns' => $query->latest()->paginate(10)->withQueryString(),
      'filters' => $request->only(['search', 'status', 'sort', 'direction']),
      'statuses' => CampaignStatus::cases(),
    ]);
  }

  public function create()
  {
    $team = auth()->user()->currentTeam;

    return Inertia::render('campaigns/Create', [
      'templates' => $team->templates()->select('id', 'uuid', 'name')->get(),
      'lists' => $team->mailingLists()->select('id', 'uuid', 'name')->get()
    ]);
  }

  public function edit(Campaign $campaign)
  {
    // $this->authorize('update', $campaign);

    // Load necessary relationships
    $campaign->load(['template:id,uuid,name,subject,content,design', 'mailingLists:id,uuid,name']);

    // Ensure we only return the necessary fields
    $campaignData = $campaign->only([
      'id', 'uuid', 'name', 'subject', 'description', 'preview_text',
      'from_name', 'from_email', 'reply_to', 'status', 'scheduled_at',
      'content', 'design'
    ]);

    return Inertia::render('campaigns/Edit', [
      'campaign' => $campaignData,

      'mergeTags' => MergeTag::query()
        ->select(['id', 'key', 'name', 'description', 'default', 'category'])
        ->orderBy('category')
        ->orderBy('name')
        ->get()
        ->groupBy('category')
        ->map(fn($tags) => $tags->map(fn($tag) => [
          'id' => $tag->id,
          'key' => $tag->key,
          'name' => $tag->name,
          'description' => $tag->description,
          'default' => $tag->default ?? ''
        ]))
        ->toArray(),
      'templates' => auth()->user()->currentTeam->templates()->select('id', 'uuid', 'name')->get(),
      'lists' => auth()->user()->currentTeam->mailingLists()->select('id', 'uuid', 'name')->get()
    ]);
  }

  public function store(StoreCampaignRequest $request)
  {
    try {
      $campaign = $this->campaignService->create($request->validated());

      return redirect()
        ->route('app.campaigns.show', $campaign->uuid)
        ->with('success', 'Campaign created successfully.');
    } catch (\Exception $e) {
      report($e);

      return back()->withErrors([
        'error' => 'Could not create campaign. Please try again.'
      ]);
    }
  }

  public function show(Campaign $campaign)
  {
    $this->authorize('view', $campaign);

    return Inertia::render('campaigns/Show', [
      'campaign' => array_merge($campaign->toArray(), [
        'stats' => $campaign->stats,
        'lists' => $campaign->mailingLists()->select('id', 'name')->get(),
        'events' => $campaign->events()
          ->with('subscriber:id,email')
          ->latest()
          ->take(10)
          ->get()
      ])
    ]);
  }

  public function update(Request $request, Campaign $campaign)
  {
    if (!in_array($campaign->status, ['draft', 'sent', 'completed'])) {
      return back()->withErrors([
        'error' => 'This campaign cannot be edited in its current status.'
      ]);
    }

    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'subject' => 'required|string|max:255',
      'preview_text' => 'nullable|string|max:255',
      'template_id' => 'nullable|exists:email_templates,id',
      'content' => 'required|string',
      'design' => 'required|array',
      'merge_tags' => 'required|array',
      'from_name' => 'required|string|max:255',
      'from_email' => 'required|email|max:255',
      'reply_to' => 'required|email|max:255',
      'list_ids' => 'required|array',
      'list_ids.*' => 'exists:mailing_lists,id',
      'scheduled_at' => 'nullable|date|after:now',
    ]);

    try {
      DB::beginTransaction();

      // Update campaign
      $campaign->update($validated);

      // Sync mailing lists
      $campaign->mailingLists()->sync($validated['list_ids']);

      DB::commit();

      return redirect()
        ->route('app.campaigns.edit', $campaign->uuid)
        ->with('success', 'Campaign updated successfully.');
    } catch (\Exception $e) {
      DB::rollBack();
      report($e);

      return back()->withErrors([
        'error' => 'Failed to update campaign. Please try again.'
      ]);
    }
  }

  public function send(Campaign $campaign): \Illuminate\Http\RedirectResponse
  {
    try {
      $this->campaignService->send($campaign);

      return redirect()
        ->route('app.campaigns.index')
        ->with('success', 'Campaign sending started successfully.');
    } catch (\Exception $e) {
      report($e);

      return back()->withErrors([
        'error' => $e->getMessage() ?? 'Failed to send campaign.',
      ]);
    }
  }
}
