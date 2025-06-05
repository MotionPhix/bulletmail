<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuotaResource;
use Illuminate\Http\JsonResponse;

class QuotaController extends Controller
{
  public function show(): QuotaResource
  {
    return new QuotaResource(auth()->user()->emailQuota);
  }

  public function usage(): JsonResponse
  {
    $usage = auth()->user()->getQuotaUsagePercentage();

    return response()->json([
      'daily' => $usage['daily'],
      'monthly' => $usage['monthly'],
      'quota' => new QuotaResource(auth()->user()->emailQuota)
    ]);
  }
}
