<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;

/*class CheckTeamAccess
{
  public function handle(Request $request, Closure $next)
  {
    $user = $request->user();
    $teamUuid = $request->route('team');

    // Get team by UUID
    $team = is_string($teamUuid)
      ? Team::where('uuid', $teamUuid)->firstOrFail()
      : $teamUuid;

    if (!$team) {
      return redirect()->route('dashboard');
    }

    // Check if user belongs to team or is organization owner
    if ($team->owner_id === $user->id || $user->belongsToTeam($team)) {
      // Set as current team if not already set
      if ($user->current_team_id !== $team->id) {
        $user->forceFill(['current_team_id' => $team->id])->save();
      }
      return $next($request);
    }

    abort(403, 'You do not have access to this team.');
  }
}*/

class CheckTeamAccess
{
  public function handle(Request $request, Closure $next)
  {
    $user = $request->user();
    $teamUuid = $request->route('team');

    // If no team UUID in route, use current team
    if (!$teamUuid) {
      $team = $user->currentTeam;

      if (!$team) {
        return redirect()->route('dashboard')
          ->with('error', 'Please select a team first.');
      }
    } else {
      // Get team by UUID if provided
      $team = is_string($teamUuid)
        ? Team::where('uuid', $teamUuid)->firstOrFail()
        : $teamUuid;

      if (!$team) {
        return redirect()->route('dashboard');
      }
    }

    // Check if user belongs to team or is organization owner
    if (!($team->owner_id === $user->id || $user->belongsToTeam($team))) {
      abort(403, 'You do not have access to this team.');
    }

    // Store current team in request for controller access
    $request->attributes->set('team', $team);

    return $next($request);
  }
}
