<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that's loaded on the first page visit.
   *
   * @see https://inertiajs.com/server-side-setup#root-template
   *
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determines the current asset version.
   *
   * @see https://inertiajs.com/asset-versioning
   */
  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  /**
   * Define the props that are shared by default.
   *
   * @see https://inertiajs.com/shared-data
   *
   * @return array<string, mixed>
   */
  public function share(Request $request): array
  {
    [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

    return [
      ...parent::share($request),
      'name' => config('app.name'),
      'quote' => ['message' => trim($message), 'author' => trim($author)],
      'auth' => [
        'user' => $request->user() ? [
          'id' => $request->user()->id,
          'name' => $request->user()->name,
          'email' => $request->user()->email,
          'avatar' => $request->user()->avatar,
          'can' => [
            // Organization Permissions
            'create_organizations' => $request->user()->can('create', \App\Models\Organization::class),
            'view_organizations' => $request->user()->can('viewAny', \App\Models\Organization::class),

            // Team Permissions (from Spatie)
            'team:view' => $request->user()->hasPermissionTo('team:view'),
            'team:settings:edit' => $request->user()->hasPermissionTo('team:settings:edit'),
            'team:delete' => $request->user()->hasPermissionTo('team:delete'),

            // Member Management
            'member:view' => $request->user()->hasPermissionTo('member:view'),
            'member:invite' => $request->user()->hasPermissionTo('member:invite'),
            'member:remove' => $request->user()->hasPermissionTo('member:remove'),
            'member:role:assign' => $request->user()->hasPermissionTo('member:role:assign'),

            // Campaign Permissions
            'campaign:view' => $request->user()->hasPermissionTo('campaign:view'),
            'campaign:create' => $request->user()->hasPermissionTo('campaign:create'),
            'campaign:edit' => $request->user()->hasPermissionTo('campaign:edit'),
            'campaign:delete' => $request->user()->hasPermissionTo('campaign:delete'),
            'campaign:send' => $request->user()->hasPermissionTo('campaign:send'),
            'campaign:schedule' => $request->user()->hasPermissionTo('campaign:schedule'),

            // Template Permissions
            'template:view' => $request->user()->hasPermissionTo('template:view'),
            'template:create' => $request->user()->hasPermissionTo('template:create'),
            'template:edit' => $request->user()->hasPermissionTo('template:edit'),
            'template:delete' => $request->user()->hasPermissionTo('template:delete'),

            // Subscriber Permissions
            'subscriber:view' => $request->user()->hasPermissionTo('subscriber:view'),
            'subscriber:create' => $request->user()->hasPermissionTo('subscriber:create'),
            'subscriber:edit' => $request->user()->hasPermissionTo('subscriber:edit'),
            'subscriber:delete' => $request->user()->hasPermissionTo('subscriber:delete'),
            'subscriber:import' => $request->user()->hasPermissionTo('subscriber:import'),
            'subscriber:export' => $request->user()->hasPermissionTo('subscriber:export'),

            // Analytics Permissions
            'analytics:view' => $request->user()->hasPermissionTo('analytics:view'),
            'analytics:export' => $request->user()->hasPermissionTo('analytics:export'),

            // Automation Permissions
            'automation:view' => $request->user()->hasPermissionTo('automation:view'),
            'automation:create' => $request->user()->hasPermissionTo('automation:create'),
            'automation:edit' => $request->user()->hasPermissionTo('automation:edit'),
            'automation:delete' => $request->user()->hasPermissionTo('automation:delete'),
          ],
        ] : null,
        'current_team' => $request->user()?->currentTeam,
        'current_organization' => $request->user()?->currentTeam?->organization,
        'teams' => $request->user()?->allTeams()->map(function ($team) {
          return [
            'uuid' => $team->uuid,
            'name' => $team->name,
            'personal_team' => $team->personal_team,
            'organization_id' => $team->organization_id,
            'organization_name' => $team->organization?->name,
            'organization_uuid' => $team->organization?->uuid,
            'plan' => $team?->plan ?? 'Free',
            'can' => [
              'view' => $team->hasPermissionTo('team:view'),
              'settings_edit' => $team->hasPermissionTo('team:settings:edit'),
              'delete' => $team->hasPermissionTo('team:delete'),
            ],
          ];
        })->values() ?: [],
      ],
      'ziggy' => [
        ...(new Ziggy)->toArray(),
        'location' => $request->url(),
      ],
      'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
  }
}
