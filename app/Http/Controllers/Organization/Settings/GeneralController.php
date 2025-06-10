<?php

namespace App\Http\Controllers\Organization\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\Settings\UpdateGeneralSettingsRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
  public function edit(): Response
  {
    $organization = auth()->user()->currentTeam->organization;

    return Inertia::render('organization/settings/General', [
      'organization' => $organization,
      'availableSizes' => [
        '1-10',
        '11-50',
        '51-200',
        '201-500',
        '501+'
      ],
      'availableIndustries' => [
        'technology',
        'healthcare',
        'finance',
        'education',
        'retail',
        'manufacturing',
        'other'
      ]
    ]);
  }

  public function update(
    UpdateGeneralSettingsRequest $request,
    Organization $organization
  ): RedirectResponse
  {
    $organization->update($request->validated());

    return back()->with('success', 'Organization settings updated successfully.');
  }
}
