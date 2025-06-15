<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
  public function preview(Campaign $campaign)
  {
    return response()->json([
      'html' => $campaign->getPreviewContent()
    ]);
  }
}
