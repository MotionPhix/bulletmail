<?php

namespace App\Http\Controllers;

use App\Imports\SubscribersImport;
use App\Models\Subscriber;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Http\Requests\StoreSubscriberRequest;
use App\Models\Campaign;
use App\Models\CampaignEvent;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SubscriberController extends Controller
{
  use AuthorizesRequests;

  public function index(Request $request)
  {
    $query = Subscriber::query()
      ->where('team_id', $request->user()->currentTeam->id)
      ->with(['campaignEvents' => function ($query) {
        $query->select('id', 'subscriber_id', 'type', 'created_at');
      }]);

    // Search
    if ($request->search) {
      $query->where(function ($q) use ($request) {
        $q->where('email', 'like', "%{$request->search}%")
          ->orWhere('first_name', 'like', "%{$request->search}%")
          ->orWhere('last_name', 'like', "%{$request->search}%")
          ->orWhere('company', 'like', "%{$request->search}%");
      });
    }

    // Filter by status
    if ($request->status && $request->status !== 'all') {
      $query->where('status', $request->status);
    }

    // Sort
    $sortField = $request->sort ?? 'created_at';
    $sortDirection = $request->direction ?? 'desc';
    $query->orderBy($sortField, $sortDirection);

    $subscribers = $query->paginate(10)
      ->withQueryString()
      ->through(fn($subscriber) => [
        'id' => $subscriber->id,
        'email' => $subscriber->email,
        'first_name' => $subscriber->first_name,
        'last_name' => $subscriber->last_name,
        'company' => $subscriber->company,
        'status' => $subscriber->status,
        'metadata' => $subscriber->metadata,
        'unsubscribed_at' => $subscriber->unsubscribed_at,
        'created_at' => $subscriber->created_at,
        'campaign_stats' => [
          'total_received' => $subscriber->campaignEvents->where('type', 'sent')->count(),
          'total_opened' => $subscriber->campaignEvents->where('type', 'opened')->count(),
          'total_clicked' => $subscriber->campaignEvents->where('type', 'clicked')->count(),
        ]
      ]);

    $stats = [
      'total' => Subscriber::where('team_id', $request->user()->currentTeam->id)->count(),
      'subscribed' => Subscriber::where('team_id', $request->user()->currentTeam->id)
        ->where('status', Subscriber::STATUS_SUBSCRIBED)->count(),
      'unsubscribed' => Subscriber::where('team_id', $request->user()->currentTeam->id)
        ->where('status', Subscriber::STATUS_UNSUBSCRIBED)->count(),
      'bounced' => Subscriber::where('team_id', $request->user()->currentTeam->id)
        ->where('status', Subscriber::STATUS_BOUNCED)->count(),
      'complained' => Subscriber::where('team_id', $request->user()->currentTeam->id)
        ->where('status', Subscriber::STATUS_COMPLAINED)->count(),
    ];

    return Inertia::render('Subscribers/Index', [
      'subscribers' => $subscribers,
      'filters' => $request->only(['search', 'status', 'sort', 'direction']),
      'stats' => $stats
    ]);
  }

  public function show(Subscriber $subscriber)
  {
    $this->authorize('view', $subscriber);

    // Get campaign events for this subscriber
    $campaignEvents = $subscriber->campaignEvents()
      ->with('campaign')
      ->orderBy('created_at', 'desc')
      ->get();

    // Calculate statistics
    $totalCampaigns = $subscriber->campaigns()->count();

    $stats = [
      'total_campaigns' => $totalCampaigns,
      'open_rate' => $totalCampaigns > 0
        ? $campaignEvents->where('type', 'open')->unique('campaign_id')->count() / $totalCampaigns
        : 0,
      'click_rate' => $totalCampaigns > 0
        ? $campaignEvents->where('type', 'click')->unique('campaign_id')->count() / $totalCampaigns
        : 0,
      'bounce_rate' => $totalCampaigns > 0
        ? $campaignEvents->where('type', 'bounce')->count() / $totalCampaigns
        : 0,
      'spam_rate' => $totalCampaigns > 0
        ? $campaignEvents->where('type', 'complaint')->count() / $totalCampaigns
        : 0,
    ];

    // Get campaigns data
    $campaigns = $subscriber->campaigns()
      ->with(['events' => function ($query) use ($subscriber) {
        $query->where('subscriber_id', $subscriber->id);
      }])
      ->orderBy('sent_at', 'desc')
      ->get()
      ->map(function ($campaign) {
        return [
          'id' => $campaign->id,
          'name' => $campaign->name,
          'subject' => $campaign->subject,
          'sent_at' => $campaign->sent_at,
          'stats' => [
            'opens' => $campaign->events->where('type', 'open')->count(),
            'clicks' => $campaign->events->where('type', 'click')->count(),
            'bounces' => $campaign->events->where('type', 'bounce')->count(),
            'complaints' => $campaign->events->where('type', 'complaint')->count(),
          ],
        ];
      });

    // Get activity timeline
    $activity = $campaignEvents
      ->map(function ($event) {
        return [
          'id' => $event->id,
          'type' => $event->type,
          'campaign_name' => $event->campaign->name,
          'created_at' => $event->created_at,
        ];
      });

    return inertia('Subscribers/Show', [
      'subscriber' => array_merge($subscriber->toArray(), [
        'stats' => $stats,
        'campaigns' => $campaigns,
        'activity' => $activity,
      ]),
    ]);
  }

  public function update(Request $request, Subscriber $subscriber)
  {
    $this->authorize('update', $subscriber);

    $validated = $request->validate([
      'email' => ['required', 'email', Rule::unique('subscribers')
        ->where('team_id', $request->user()->currentTeam->id)
        ->ignore($subscriber->id)],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'company' => ['nullable', 'string', 'max:255'],
      'status' => ['required', Rule::in([
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNSUBSCRIBED,
        Subscriber::STATUS_BOUNCED,
        Subscriber::STATUS_COMPLAINED,
      ])],
    ]);

    $subscriber->update($validated);

    return redirect()->back();
  }

  public function unsubscribe(Request $request, Subscriber $subscriber)
  {
    $campaign = Campaign::find($request->query('campaign'));

    if (!$subscriber || $subscriber->status === Subscriber::STATUS_UNSUBSCRIBED) {
      return response()->json([
        'message' => 'Subscription not found or already unsubscribed'
      ], 404);
    }

    DB::transaction(function () use ($subscriber, $campaign) {
      // Update subscriber status
      $subscriber->update([
        'status' => Subscriber::STATUS_UNSUBSCRIBED,
        'unsubscribed_at' => now()
      ]);

      // Log unsubscribe event if campaign exists
      if ($campaign) {
        CampaignEvent::create([
          'campaign_id' => $campaign->id,
          'subscriber_id' => $subscriber->id,
          'type' => CampaignEvent::TYPE_UNSUBSCRIBED,
          'metadata' => [
            'reason' => 'user_initiated',
            'timestamp' => now()->timestamp
          ]
        ]);
      }

      // Update campaign stats if campaign exists
      if ($campaign && $campaign->stats) {
        $campaign->stats->increment('unsubscribed_count');
      }
    });

    return inertia('Unsubscribe/Success', [
      'message' => 'You have been successfully unsubscribed.'
    ]);
  }

  public function store(StoreSubscriberRequest $request)
  {
    $subscriber = $request->user()->currentTeam->subscribers()->create($request->validated());

    return back()->with('success', 'Subscriber added successfully.');
  }

  public function destroy(Subscriber $subscriber)
  {
    $subscriber->delete();

    return back()->with('success', 'Subscriber removed successfully.');
  }

  public function bulkDestroy(Request $request)
  {
    $validated = $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:subscribers,id'
    ]);

    Subscriber::whereIn('id', $validated['ids'])->delete();

    return back()->with('success', 'Selected subscribers removed successfully.');
  }

  public function bulkUpdate(Request $request)
  {
    $validated = $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:subscribers,id',
      'status' => ['required', Rule::in([
        Subscriber::STATUS_SUBSCRIBED,
        Subscriber::STATUS_UNSUBSCRIBED,
        Subscriber::STATUS_BOUNCED,
        Subscriber::STATUS_COMPLAINED
      ])]
    ]);

    Subscriber::whereIn('id', $validated['ids'])
      ->update(['status' => $validated['status']]);

    return back()->with('success', 'Selected subscribers updated successfully.');
  }

  /**
   * Import subscribers from a file
   *
   * @param Request $request
   * @return \Illuminate\Http\RedirectResponse
   */
  public function import(Request $request)
  {
    $validated = $request->validate([
      'file' => [
        'required',
        'file',
        'mimes:csv,txt,xlsx,xls',  // Allow Excel and CSV files
        'max:10240', // 10MB max
      ]
    ]);

    try {
      DB::beginTransaction();

      $team = $request->user()->currentTeam;
      $file = $request->file('file');
      $extension = $file->getClientOriginalExtension();

      // Prepare import results tracking
      $importResults = [
        'total' => 0,
        'imported' => 0,
        'updated' => 0,
        'failed' => 0,
        'errors' => []
      ];

      if (in_array($extension, ['xlsx', 'xls'])) {
        // Handle Excel files using Laravel Excel
        $import = new SubscribersImport($team, $importResults);
        Excel::import($import, $file);
        $importResults = $import->getResults();
      } else {
        // Handle CSV files
        $importResults = $this->processCsvFile($file, $team);
      }

      DB::commit();

      // Prepare response message
      $message = $this->prepareImportResponseMessage($importResults);

      if ($importResults['failed'] > 0) {
        return back()->with([
          'warning' => $message,
          'import_errors' => $importResults['errors']
        ]);
      }

      return back()->with('success', $message);
    } catch (\Exception $e) {
      DB::rollBack();
      report($e);
      return back()->with('error', 'Import failed: ' . $e->getMessage());
    }
  }

  /**
   * Process CSV file for import
   *
   * @param \Illuminate\Http\UploadedFile $file
   * @param \App\Models\Team $team
   * @return array
   */
  protected function processCsvFile($file, $team)
  {
    $results = [
      'total' => 0,
      'imported' => 0,
      'updated' => 0,
      'failed' => 0,
      'errors' => []
    ];

    $handle = fopen($file->getPathname(), 'r');
    $headers = null;
    $row = 1;

    while (($data = fgetcsv($handle)) !== false) {
      if (!$headers) {
        $headers = $this->normalizeHeaders($data);
        continue;
      }

      $results['total']++;
      $row++;

      try {
        $subscriberData = $this->mapRowToSubscriberData($data, $headers);

        // Validate the data
        $validator = Validator::make($subscriberData, [
          'email' => ['required', 'email', Rule::unique('subscribers', 'email')
            ->where('team_id', $team->id)
            ->ignore(optional(Subscriber::where('email', $subscriberData['email'])
              ->where('team_id', $team->id)
              ->first())->id)],
          'first_name' => 'required|string|max:255',
          'last_name' => 'required|string|max:255',
          'company' => 'nullable|string|max:255',
          'status' => [
            'nullable',
            Rule::in([
              Subscriber::STATUS_SUBSCRIBED,
              Subscriber::STATUS_UNSUBSCRIBED,
              Subscriber::STATUS_BOUNCED,
              Subscriber::STATUS_COMPLAINED
            ])
          ]
        ]);

        if ($validator->fails()) {
          $results['failed']++;
          $results['errors'][] = [
            'row' => $row,
            'data' => $subscriberData,
            'errors' => $validator->errors()->toArray()
          ];
          continue;
        }

        // Set default status if not provided
        $subscriberData['status'] ??= Subscriber::STATUS_SUBSCRIBED;

        // Try to find existing subscriber
        $subscriber = Subscriber::where('email', $subscriberData['email'])
          ->where('team_id', $team->id)
          ->first();

        if ($subscriber) {
          $subscriber->update($subscriberData);
          $results['updated']++;
        } else {
          $team->subscribers()->create(array_merge($subscriberData, [
            'user_id' => auth()->id()
          ]));

          $results['imported']++;
        }
      } catch (\Exception $e) {

        $results['failed']++;

        $results['errors'][] = [
          'row' => $row,
          'data' => $data,
          'errors' => ['system' => [$e->getMessage()]]
        ];
      }
    }

    fclose($handle);
    return $results;
  }

  /**
   * Normalize CSV headers
   *
   * @param array $headers
   * @return array
   */
  protected function normalizeHeaders($headers)
  {
    return array_map(function ($header) {
      return Str::snake(strtolower(trim($header)));
    }, $headers);
  }

  /**
   * Map CSV row data to subscriber fields
   *
   * @param array $row
   * @param array $headers
   * @return array
   */
  protected function mapRowToSubscriberData($row, $headers)
  {
    $data = array_combine($headers, $row);

    return [
      'email' => $data['email'] ?? null,
      'first_name' => $data['first_name'] ?? null,
      'last_name' => $data['last_name'] ?? null,
      'company' => $data['company'] ?? null,
      'status' => $data['status'] ?? Subscriber::STATUS_SUBSCRIBED,
    ];
  }

  /**
   * Prepare import response message
   *
   * @param array $results
   * @return string
   */
  protected function prepareImportResponseMessage($results)
  {
    $message = [];

    if ($results['imported'] > 0) {
      $message[] = "{$results['imported']} subscribers imported";
    }

    if ($results['updated'] > 0) {
      $message[] = "{$results['updated']} subscribers updated";
    }

    if ($results['failed'] > 0) {
      $message[] = "{$results['failed']} rows failed";
    }

    return implode(', ', $message) . '.';
  }

  public function export(Request $request)
  {
    $fileName = 'subscribers-' . now()->format('Y-m-d') . '.csv';

    return response()->streamDownload(function () use ($request) {
      $subscribers = Subscriber::where('team_id', $request->user()->currentTeam->id)
        ->get(['email', 'first_name', 'last_name', 'company', 'status', 'created_at']);

      $file = fopen('php://output', 'w');
      fputcsv($file, ['Email', 'First Name', 'Last Name', 'Company', 'Status', 'Joined Date']);

      foreach ($subscribers as $subscriber) {
        fputcsv($file, [
          $subscriber->email,
          $subscriber->first_name,
          $subscriber->last_name,
          $subscriber->company,
          $subscriber->status,
          $subscriber->created_at->format('Y-m-d H:i:s')
        ]);
      }

      fclose($file);
    }, $fileName);
  }
}
