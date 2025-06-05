<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamMemberRequest;
use App\Http\Resources\UserResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TeamMemberController extends Controller
{
  public function index(Team $team)
  {
    $this->authorize('viewMembers', $team);
    return UserResource::collection($team->users);
  }

  public function store(TeamMemberRequest $request, Team $team): JsonResponse
  {
    $this->authorize('addMember', $team);

    $user = User::where('email', $request->email)->first();
    $team->users()->attach($user, ['role' => $request->role]);

    return response()->json([
      'message' => 'Team member added successfully',
      'user' => new UserResource($user)
    ], 201);
  }

  public function update(TeamMemberRequest $request, Team $team, User $user): JsonResponse
  {
    $this->authorize('updateMember', $team);

    $team->users()->updateExistingPivot($user->id, [
      'role' => $request->role
    ]);

    return response()->json([
      'message' => 'Team member role updated successfully'
    ]);
  }

  public function destroy(Team $team, User $user): JsonResponse
  {
    $this->authorize('removeMember', $team);
    $team->users()->detach($user);

    return response()->json([
      'message' => 'Team member removed successfully'
    ]);
  }
}
