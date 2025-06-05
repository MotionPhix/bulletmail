<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifySendGridWebhook
{
  public function handle(Request $request, Closure $next)
  {
    if (!$request->hasHeader('X-Twilio-Email-Event-Webhook-Signature')) {
      return response()->json(['error' => 'Missing signature'], 401);
    }

    return $next($request);
  }
}
