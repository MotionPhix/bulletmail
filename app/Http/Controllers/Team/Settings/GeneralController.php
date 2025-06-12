<?php

namespace App\Http\Controllers\Team\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\Settings\UpdateGeneralSettingsRequest;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display the general settings page for the team.
   */
  public function edit(Team $team): Response
  {
    $this->authorize('update', $team);

    return Inertia::render('team/settings/General', [
      'team' => $team,
      'permissions' => [
        'canUpdateTeam' => auth()->user()->can('update', $team),
      ]
    ]);
  }

  public function update(UpdateGeneralSettingsRequest $request, Team $team): RedirectResponse
  {
    $this->authorize('update', $team);

    $team->update($request->validated());

    return back()->with('success', 'Team settings updated successfully.');
  }
}
