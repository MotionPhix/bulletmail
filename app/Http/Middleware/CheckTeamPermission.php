<?php

namespace App\Http\Middleware;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;

class CheckTeamPermission
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next, string $permission)
  {
    $user = $request->user();
    $teamUuid = $request->route('team');

    // Get team by UUID
    $team = is_string($teamUuid)
      ? Team::where('uuid', $teamUuid)->firstOrFail()
      : $teamUuid;

    if (!$team || !$user->hasTeamPermission($team, $permission)) {
      abort(403);
    }

    return $next($request);
  }
}
