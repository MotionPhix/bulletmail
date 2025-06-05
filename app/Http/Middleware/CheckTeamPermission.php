<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTeamPermission
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next, string $permission): Response
  {
    $user = $request->user();
    $team = $user->currentTeam;

    if (!$team || !$user->hasTeamPermission($team, $permission)) {
      abort(403);
    }

    return $next($request);
  }
}
