<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
  public function index(): AnonymousResourceCollection
  {
    $teams = auth()->user()->allTeams();
    return TeamResource::collection($teams);
  }

  public function store(TeamRequest $request): JsonResponse
  {
    $team = auth()->user()->ownedTeams()->create($request->validated());

    return response()->json([
      'message' => 'Team created successfully',
      'team' => new TeamResource($team)
    ], 201);
  }

  public function show(Team $team): TeamResource
  {
    $this->authorize('view', $team);
    return new TeamResource($team->load('members', 'invitations'));
  }

  public function update(TeamRequest $request, Team $team): JsonResponse
  {
    $this->authorize('update', $team);
    $team->update($request->validated());

    return response()->json([
      'message' => 'Team updated successfully',
      'team' => new TeamResource($team)
    ]);
  }

  public function destroy(Team $team): JsonResponse
  {
    $this->authorize('delete', $team);
    $team->delete();

    return response()->json([
      'message' => 'Team deleted successfully'
    ]);
  }
}
