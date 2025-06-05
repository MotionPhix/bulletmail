<?php

namespace App\Http\Controllers;

use App\Exports\CampaignsExport;
use App\Http\Filters\CampaignFilter;
use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\MailingList;
use App\Models\Segment;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CampaignController extends Controller
{
  public function __construct(
    protected CampaignService $campaignService
  )
  {
  }

  public function index(Request $request)
  {
    $filter = new CampaignFilter($request);

    $campaigns = Campaign::select([
      'id',
      'name',
      'subject',
      'status',
      'created_at',
      'scheduled_at',
      'sent_at',
    ])
      ->withCount('events as recipient_count')
      ->when(auth()->user()->currentTeam, function ($query, $team) {
        $query->where('team_id', $team->id);
      })
      ->tap(fn($query) => $filter->apply($query))
      ->paginate($request->per_page ?? 10)
      ->withQueryString();

    return Inertia::render('Campaigns/Index', [
      'campaigns' => $campaigns,
      'filters' => $filter->getFilters()
    ]);
  }

  public function create()
  {
    return Inertia::render('Campaigns/Form', [
      'templates' => EmailTemplate::all(),
      'campaign' => new Campaign(),
      'userSettings' => auth()->user()->settings()->first([
        'sender_settings->default_sender_name as from_name',
        'sender_settings->default_sender_email as from_email',
        'sender_settings->reply_to as reply_to'
      ])
    ]);
  }

  public function draft(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'subject' => 'required|string|max:255',
      'from_name' => 'required|string|max:255',
      'current_step' => ['required', 'integer', 'min:1', 'max:2'],
      'content' => 'nullable|string|max:255',
      'from_email' => [
        'required',
        'email',
        function ($attribute, $value, $fail) {
          // Add additional email validation if needed
          if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('The from email must be a valid email address.');
          }
        }
      ],
      'reply_to' => [
        'nullable',
        'email',
        function ($attribute, $value, $fail) {
          if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('The reply-to email must be a valid email address.');
          }
        }
      ],
      'template_id' => [
        'nullable',
        'exists:email_templates,id',
        function ($attribute, $value, $fail) {
          if ($value) {
            $template = EmailTemplate::find($value);
            if (!$template) {
              $fail('The selected template does not exist.');
            }
            // Add any additional template validation
          }
        }
      ],
      'recipients.mailingLists' => ['required_if:recipients.segments,=,null', 'array'],
      'recipients.segments' => ['required_if:recipients.mailingLists,=,null', 'array'],
      'recipients.excludedLists' => ['nullable', 'array'],
      'settings' => ['required', 'array'],
      'settings.track_opens' => ['required', 'boolean'],
      'settings.track_clicks' => ['required', 'boolean'],
      'settings.schedule_send' => ['required', 'boolean'],
      'settings.scheduled_at' => ['nullable', 'required_if:settings.schedule_send,true', 'date'],
      'settings.timezone' => ['required', 'string'],
    ], [
      'name.required' => 'Please provide a name for your campaign.',
      'subject.required' => 'Email subject line is required.',
      'from_name.required' => 'Please specify the sender name.',
      'from_email.required' => 'Please provide a valid sender email address.',
      'from_email.email' => 'The sender email must be a valid email address.',
      'reply_to.email' => 'The reply-to email must be a valid email address.',
      'recipients.required' => 'Please select campaign recipients.',
    ]);

    // Custom validation for recipients
    $this->validateRecipientsArray($validated['recipients']);

    // If we have an ID, update the existing draft
    if ($request->has('id') && !empty($request->id)) {
      $campaign = Campaign::findOrFail($request->id);
      $campaign = $this->campaignService->update($campaign, $validated);
    } else {
      // Create new draft campaign
      $campaign = $this->campaignService->create($validated);
    }

    return redirect()->route('campaigns.edit', [
      'campaign' => $campaign->id,
    ]);
  }

  protected function validateRecipientsArray(array $recipients)
  {
    // Convert objects to IDs if necessary
    $listIds = collect($recipients['mailingLists'] ?? [])
      ->map(fn($list) => is_array($list) ? $list['id'] : $list)
      ->all();

    $segmentIds = collect($recipients['segments'] ?? [])
      ->map(fn($segment) => is_array($segment) ? $segment['id'] : $segment)
      ->all();

    $excludedListIds = collect($recipients['excludedLists'] ?? [])
      ->map(fn($list) => is_array($list) ? $list['id'] : $list)
      ->all();

    if (empty($listIds) && empty($segmentIds)) {
      throw ValidationException::withMessages([
        'recipients' => 'Please select at least one list or segment of recipients.'
      ]);
    }

    $teamId = auth()->user()->currentTeam->id;

    // Validate mailing lists
    if (!empty($listIds)) {
      $validListCount = MailingList::query()
        ->where('team_id', $teamId)
        ->whereIn('id', $listIds)
        ->count();

      if ($validListCount !== count($listIds)) {
        throw ValidationException::withMessages([
          'recipients' => 'One or more selected lists are invalid.'
        ]);
      }
    }

    // Similar validation for segments and excluded lists...
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'subject' => 'required|string|max:255',
      'from_name' => 'required|string|max:255',
      'from_email' => 'required|email',
      'reply_to' => 'nullable|email',
      'current_step' => ['required', 'integer', 'min:1', 'max:2'],
      'content' => 'required|string',
      'template_id' => 'nullable|exists:templates,id',
      'scheduled_at' => 'nullable|date|after:now',
      'recipients' => 'required|array',
      'settings' => 'nullable|array',
      'team_id' => auth()->user()->currentTeam->id,
    ], [
      'name.required' => 'Please provide a name for your campaign.',
      'subject.required' => 'Email subject line is required.',
      'from_name.required' => 'Please specify the sender name.',
      'from_email.required' => 'Please provide a valid sender email address.',
      'from_email.email' => 'The sender email must be a valid email address.',
      'reply_to.email' => 'The reply-to email must be a valid email address.',
      'recipients.required' => 'Please select campaign recipients.',
    ]);

    $campaign = $this->campaignService->create($validated);

    return redirect()->route('campaigns.edit', $campaign)
      ->with('success', 'Index created successfully.');
  }

  public function show(Campaign $campaign)
  {
    $campaign->load([
      'template',
      'user',
      'team',
      'events' => fn($query) => $query->latest()->limit(100)
    ]);

    return Inertia::render('Campaigns/Show', [
      'campaign' => $campaign,
      'stats' => $this->campaignService->getDetailedStats($campaign),
      'chartData' => [
        'opens' => $this->campaignService->getOpenRateOverTime($campaign),
        'clicks' => $this->campaignService->getClickRateOverTime($campaign),
        'engagement' => $this->campaignService->getEngagementMetrics($campaign)
      ]
    ]);
  }

  public function edit(Request $request, Campaign $campaign)
  {
    $campaign->load('template');

    return Inertia::render('Campaigns/Form', [
      'campaign' => $campaign,
      'templates' => $request->user()->currentTeam->templates,
      'userSettings' => auth()->user()->settings()->first([
        'sender_settings->default_sender_name as from_name',
        'sender_settings->default_sender_email as from_email',
        'sender_settings->reply_to as reply_to'
      ]),
      'step' => $campaign->current_step ?? 2,
    ]);
  }

  public function update(Request $request, Campaign $campaign)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'subject' => 'required|string|max:255',
      'from_name' => 'required|string|max:255',
      'from_email' => 'required|email',
      'reply_to' => 'nullable|email',
      'current_step' => ['required', 'integer', 'min:1', 'max:2'],
      'content' => 'nullable|string',
      'template_id' => 'nullable|exists:templates,id',
      'scheduled_at' => 'nullable|date|after:now',
      'recipients' => 'required|array',
      'settings' => 'nullable|array',
    ]);

    $this->campaignService->update($campaign, $validated);

    return redirect()->route('campaigns.edit', $campaign)
      ->with('success', 'Index updated successfully.');
  }

  public function destroy(Campaign $campaign)
  {
    try {
      $this->campaignService->delete($campaign);

      return redirect()->route('campaigns.index')
        ->with('success', 'Campaign deleted successfully.');
    } catch (\Exception $e) {
      return back()->withErrors(['error' => $e->getMessage()]);
    }
  }

  public function schedule(Request $request, Campaign $campaign)
  {
    $validated = $request->validate([
      'scheduled_at' => 'required|date|after:now',
      'timezone' => 'required|string'
    ]);

    try {
      $this->campaignService->schedule($campaign, $validated['scheduled_at'], $validated['timezone']);

      return redirect()->route('campaigns.show', $campaign)
        ->with('success', 'Campaign scheduled successfully.');
    } catch (\Exception $e) {
      return back()->withErrors(['error' => $e->getMessage()]);
    }
  }

  public function send(Campaign $campaign)
  {
    try {
      $this->campaignService->sendCampaign($campaign);

      return redirect()->route('campaigns.show', $campaign)
        ->with('success', 'Campaign sending has been initiated.');
    } catch (\Exception $e) {
      return back()->withErrors(['error' => $e->getMessage()]);
    }
  }

  public function duplicate(Campaign $campaign)
  {
    try {
      $newCampaign = $this->campaignService->duplicate($campaign);

      return redirect()->route('campaigns.edit', $newCampaign)
        ->with('success', 'Campaign duplicated successfully.');
    } catch (\Exception $e) {
      return back()->withErrors(['error' => $e->getMessage()]);
    }
  }

  public function preview(Campaign $campaign)
  {
    return Inertia::render('Campaigns/Preview', [
      'campaign' => $campaign->load('template')
    ]);
  }

  public function stats(Campaign $campaign)
  {
    return Inertia::render('Campaigns/Stats', [
      'campaign' => $campaign->load(['template', 'user', 'team']),
      'stats' => $this->campaignService->getDetailedStats($campaign),
      'chartData' => [
        'opens' => $this->campaignService->getOpenRateOverTime($campaign),
        'clicks' => $this->campaignService->getClickRateOverTime($campaign),
        'engagement' => $this->campaignService->getEngagementMetrics($campaign)
      ]
    ]);
  }

  public function bulkDelete(Request $request)
  {
    $validated = $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:campaigns,id'
    ]);

    Campaign::whereIn('id', $validated['ids'])->delete();

    return back()->with('success', 'Selected campaigns have been deleted.');
  }

  public function export(Request $request)
  {
    return Excel::download(
      new CampaignsExport($request->all()),
      'campaigns.xlsx'
    );
  }
}
