<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Services\SendGridService;
use Illuminate\Http\Request;

class Stats extends Controller
{
  protected $sendGridService;

  public function __construct(SendGridService $sendGridService)
  {
    $this->sendGridService = $sendGridService;
  }

  /**
   * Get campaign statistics
   *
   * @param string $campaignId
   * @return \Illuminate\Http\JsonResponse
   */
  public function show($campaignId)
  {
    try {
      $stats = $this->sendGridService->getCampaignStats($campaignId);
      return response()->json($stats);
    } catch (\Exception $e) {
      return response()->json([
        'error' => $e->getMessage()
      ], 500);
    }
  }
}
