<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
  public function handle(Request $request, Closure $next): Response
  {
    if (auth()->check()) {
      $onboardingProgress = $request->user()->onboardingProgress;

      // If onboarding is not completed and we're not already on the onboarding page
      if (!$onboardingProgress?->is_completed && $request->routeIs('onboarding.*')) {
        return redirect()->route('onboarding.index');
      }
    }

    return $next($request);
  }
}
