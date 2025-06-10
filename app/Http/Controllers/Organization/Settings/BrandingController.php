<?php

namespace App\Http\Controllers\Organization\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\Settings\UpdateBrandingSettingsRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class BrandingController extends Controller
{
  public function edit(): Response
  {
    $organization = auth()->user()->currentTeam->organization;

    return Inertia::render('organization/settings/Branding', [
      'organization' => $organization->load('media'),
      'brandingConfig' => $organization->getBrandingConfig(),
      'logo' => $organization->getFirstMediaUrl('logo'),
      'logoThumbnail' => $organization->getFirstMediaUrl('logo', 'thumb'),
      'logoEmail' => $organization->getFirstMediaUrl('logo', 'email')
    ]);
  }

  public function update(UpdateBrandingSettingsRequest $request, Organization $organization): RedirectResponse
  {
    try {
      DB::beginTransaction();

      // Update branding settings
      $organization->update([
        'primary_color' => $request->primary_color,
        'secondary_color' => $request->secondary_color,
        'email_header' => $request->email_header,
        'email_footer' => $request->email_footer,
      ]);

      // Handle logo upload if provided
      if ($request->hasFile('logo')) {
        // Clear existing logo first
        $organization->clearMediaCollection('logo');

        // Add new logo with conversions
        $organization->addMedia($request->file('logo'))
          ->usingName($organization->name . ' Logo')
          ->withCustomProperties([
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
          ])
          ->toMediaCollection('logo');
      }

      DB::commit();

      return back()->with('success', 'Branding settings updated successfully.');
    } catch (FileDoesNotExist $e) {
      DB::rollBack();
      return back()->with('error', 'The logo file could not be found.');
    } catch (FileIsTooBig $e) {
      DB::rollBack();
      return back()->with('error', 'The logo file is too large.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'An error occurred while updating branding settings.');
    }
  }

  public function deleteLogo(Organization $organization): RedirectResponse
  {
    try {
      $organization->clearMediaCollection('logo');
      return back()->with('success', 'Logo removed successfully.');
    } catch (\Exception $e) {
      return back()->with('error', 'Unable to remove logo.');
    }
  }

  public function regenerateConversions(Organization $organization): RedirectResponse
  {
    try {
      $organization->media()
        ->where('collection_name', 'logo')
        ->get()
        ->each
        ->forceConvert(['thumb', 'email']);

      return back()->with('success', 'Logo variations regenerated successfully.');
    } catch (\Exception $e) {
      return back()->with('error', 'Unable to regenerate logo variations.');
    }
  }
}
