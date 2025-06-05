<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSettings
{
  public function handle(Request $request, Closure $next): Response
  {
    $user = $request->user();
    $senderSettings = $user?->settings->sender_settings;

    // Check if sender settings are properly configured
    if (!$senderSettings['email_verified'] ||
      empty($senderSettings['default_sender_email']) ||
      empty($senderSettings['default_sender_name']) ||
      ($senderSettings['setup_required'] ?? false)) {

      // Only check on critical actions
      if ($this->isActionRequiringSettings($request)) {
        return redirect()->route('settings.email')
          ->with('warning', 'Please configure your sender settings before proceeding.');
      }
    }

    return $next($request);
  }

  private function isActionRequiringSettings(Request $request): bool
  {
    // List of routes that require sender settings
    $criticalActions = [
      'campaigns.send',
      'campaigns.schedule',
      'campaigns.store',
      'campaigns.update',
    ];

    return in_array($request->route()->getName(), $criticalActions);
  }
}
