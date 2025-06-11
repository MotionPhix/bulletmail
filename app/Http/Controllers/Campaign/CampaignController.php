<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
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
          ->orWhere('subject', 'like', "%{$search}%");
      });
    }

    if ($status = $request->input('status')) {
      $query->where('status', $status);
    }

    $sort = $request->input('sort', 'name');
    if (in_array($sort, ['name', 'status', 'scheduled_at'])) {
      $query->orderBy($sort);
    } else {
      $query->orderBy('name');
    }

    return Inertia::render('campaigns/Index', [
      'campaigns' => $query->latest()->paginate(10),
      'filters' => $request->only(['search', 'status', 'sort']),
      'statuses' => CampaignStatus::cases(),
    ]);
  }

  public function create()
  {
    $team = auth()->user()->currentTeam;

    return Inertia::render('campaigns/Create', [
      'templates' => $team->emailTemplates()->select('id', 'name')->get(),
      'lists' => $team->mailingLists()->select('id', 'name')->get()
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'subject' => 'required|string|max:255',
      'template_id' => 'required|exists:email_templates,id',
      'content' => 'required|string',
      'list_ids' => 'required|array|min:1',
      'scheduled_at' => 'nullable|date|after:now'
    ]);

    $campaign = $this->campaignService->create($validated);

    return redirect()->route('campaigns.show', $campaign)
      ->with('success', 'Campaign created successfully');
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
}
