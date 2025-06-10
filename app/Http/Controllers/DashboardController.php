<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
  public function index(Request $request): Response
  {
    $user = $request->user();
    $team = $user->currentTeam;
    $organization = $team->organization;

    // Get campaign stats through proper relationship
    $campaignStats = $team->campaigns()
      ->join('campaign_stats', 'campaigns.id', '=', 'campaign_stats.campaign_id')
      ->selectRaw('
                SUM(recipients_count) as total_sent,
                SUM(opened_count) as total_opened,
                SUM(clicked_count) as total_clicked,
                SUM(bounced_count) as total_bounced
            ')
      ->first();

    return Inertia::render('team/Dashboard', [
      'organization' => [
        'name' => $organization->name,
        'uuid' => $organization->uuid,
      ],
      'team' => [
        'name' => $team->name,
        'uuid' => $team->uuid,
        'recent_activities' => collect($team->recent_activities)->map(fn($activity) => [
          'id' => $activity['id'],
          'description' => $activity['description'],
          'causer_name' => $activity['user']['name'],
          'causer_avatar' => $activity['user']['avatar'],
          'created_at' => $activity['created_at']->diffForHumans(),
        ])
      ],
      'teamStats' => [
        'members_count' => $team->users()->count(),
        'subscribers_count' => $team->subscribers()->count(),
        'campaigns_count' => $team->campaigns()->count(),
        'active_automations' => 0, // $team->automations()->where('status', 'active')->count(),
      ],
      'campaignStats' => [
        'total_sent' => $campaignStats->total_sent ?? 0,
        'total_opened' => $campaignStats->total_opened ?? 0,
        'total_clicked' => $campaignStats->total_clicked ?? 0,
        'total_bounced' => $campaignStats->total_bounced ?? 0,
      ],
      'subscriberTrends' => $this->getSubscriberTrends($team),
    ]);
  }

  protected function getSubscriberTrends(Team $team): array
  {
    return $team->subscribers()
      ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
      ->whereYear('created_at', now()->year)
      ->groupBy('month')
      ->orderBy('month')
      ->pluck('count', 'month')
      ->toArray();
  }
}
