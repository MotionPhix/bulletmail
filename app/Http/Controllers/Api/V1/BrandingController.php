<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandingRequest;
use App\Http\Resources\BrandingResource;
use Illuminate\Http\JsonResponse;

class BrandingController extends Controller
{
  public function show(): BrandingResource
  {
    $branding = auth()->user()->branding;
    return new BrandingResource($branding);
  }

  public function update(BrandingRequest $request): JsonResponse
  {
    auth()->user()->updateBranding($request->validated());

    return response()->json([
      'message' => 'Branding updated successfully',
      'branding' => new BrandingResource(auth()->user()->branding)
    ]);
  }

  public function uploadLogo(BrandingRequest $request): JsonResponse
  {
    $this->validate($request, [
      'logo' => ['required', 'image', 'max:2048']
    ]);

    auth()->user()->updateBranding(['logo' => $request->file('logo')]);

    return response()->json([
      'message' => 'Logo uploaded successfully',
      'branding' => new BrandingResource(auth()->user()->branding)
    ]);
  }
}
