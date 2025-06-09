<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
  public function index(): Response
  {
    return Inertia::render('team/Index', [
      'teams' => auth()->user()->allTeams()
    ]);
  }

  public function show(Team $team): Response
  {
    return Inertia::render('team/Show', [
      'team' => $team->load(['organization', 'owner'])
        ->loadCount(['users', 'campaigns', 'subscribers']),
      'members' => $team->users()->withPivot('role')->get(),
      'invitations' => $team->invitations()->pending()->get()
    ]);
  }

  public function store(StoreTeamRequest $request): RedirectResponse
  {
    $this->authorize('create', Team::class);

    $team = Team::create(array_merge(
      $request->validated(),
      [
        'owner_id' => auth()->id(),
        'organization_id' => auth()->user()->currentTeam->organization_id
      ]
    ));

    return redirect()->route('teams.show', $team)
      ->with('success', 'Team created successfully.');
  }

  public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
  {
    $this->authorize('update', $team);

    $team->update($request->validated());

    return redirect()->route('teams.show', $team)
      ->with('success', 'Team updated successfully.');
  }

  public function destroy(Team $team): RedirectResponse
  {
    if ($team->personal_team) {
      return back()->with('error', 'Cannot delete personal team.');
    }

    $team->delete();

    return redirect()->route('teams.index')
      ->with('success', 'Team deleted successfully.');
  }

  public function switch(Team $team): RedirectResponse
  {
    if (!auth()->user()->belongsToTeam($team)) {
      abort(403);
    }

    auth()->user()->switchTeam($team);

    return redirect()->route('teams.show', $team)
      ->with('success', 'Switched to team successfully.');
  }
}
