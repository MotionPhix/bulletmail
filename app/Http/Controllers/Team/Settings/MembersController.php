<?php

namespace App\Http\Controllers\Team\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\Settings\InviteMemberRequest;
use App\Http\Requests\Team\Settings\UpdateMemberRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\{Team, User};
use App\Notifications\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
  use AuthorizesRequests;

  /**
   * Display the members settings page for the team.
   */
  public function index(Team $team): Response
  {
    $this->authorize('viewMembers', $team);

    return Inertia::render('team/settings/Members', [
      'team' => $team->load('owner'),
      'members' => $team->users()->withPivot('role')->get(),
      'invitations' => $team->invitations()->pending()->get(),
      'availableRoles' => ['owner', 'admin', 'member'],
      'permissions' => [
        'canInviteMembers' => auth()->user()->can('invite', $team),
        'canRemoveMembers' => auth()->user()->can('removeMembers', $team),
      ]
    ]);
  }

  public function invite(InviteMemberRequest $request, Team $team): RedirectResponse
  {
    $this->authorize('invite', $team);

    $invitation = $team->invitations()->create([
      'email' => $request->email,
      'role' => $request->role,
      'user_id' => auth()->id()
    ]);

    $invitation->notify(new TeamInvitation($team));

    return back()->with('success', 'Invitation sent successfully.');
  }

  public function update(UpdateMemberRequest $request, Team $team, User $user): RedirectResponse
  {
    $this->authorize('updateMember', [$team, $user]);

    $team->users()->updateExistingPivot($user->id, [
      'role' => $request->role
    ]);

    return back()->with('success', 'Member role updated successfully.');
  }

  public function remove(Team $team, User $user): RedirectResponse
  {
    $this->authorize('removeMembers', $team);

    if ($team->owner_id === $user->id) {
      return back()->with('error', 'Cannot remove team owner.');
    }

    $team->users()->detach($user->id);

    return back()->with('success', 'Member removed successfully.');
  }
}
