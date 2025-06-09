<?php

namespace App\Http\Controllers\Organization\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\Settings\UpdateIntegrationsSettingsRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IntegrationsController extends Controller
{
  public function edit(Organization $organization): Response
  {
    return Inertia::render('organization/settings/Integrations', [
      'organization' => $organization,
      'integrations' => $organization->integrations
    ]);
  }

  public function update(
    UpdateIntegrationsSettingsRequest $request,
    Organization $organization
  ): RedirectResponse {
    $organization->update([
      'integrations' => $request->validated()['integrations']
    ]);

    return back()->with('success', 'Integration settings updated successfully.');
  }
}
