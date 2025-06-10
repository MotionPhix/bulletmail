<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;

class CheckOrganizationAccess
{
  public function handle(Request $request, Closure $next)
  {
    $user = $request->user();
    $organizationUuid = $request->route('organization');

    // First try to get organization from route parameter
    if ($organizationUuid) {
      $organization = is_string($organizationUuid)
        ? Organization::where('uuid', $organizationUuid)->firstOrFail()
        : $organizationUuid;
    }
    // If no route parameter, get from user's current team
    else {
      $userTeam = $user->currentTeam;
      if (!$userTeam) {
        abort(403, 'No team or organization selected.');
      }
      $organization = $userTeam->organization;
    }

    if (!$organization) {
      return redirect()->route('dashboard');
    }

    // If user is org owner, allow access
    if ($organization->owner_id === $user->id) {
      return $next($request);
    }

    // If user belongs to a team in this org, allow access
    $userTeam = $user->currentTeam;
    if ($userTeam && $userTeam->organization_id === $organization->id) {
      return $next($request);
    }

    abort(403, 'You do not have access to this organization.');
  }
}
