<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

trait HasTeams
{
  public function teams(): BelongsToMany
  {
    return $this->belongsToMany(Team::class)
      ->withPivot('role')
      ->withTimestamps();
  }

  public function ownedTeams(): HasMany
  {
    return $this->hasMany(Team::class, 'owner_id');
  }

  public function currentTeam()
  {
    return $this->belongsTo(Team::class, 'current_team_id');
  }

  public function allTeams(): Collection
  {
    return $this->ownedTeams->merge($this->teams);
  }

  public function switchTeam($team): bool
  {
    if (!$this->belongsToTeam($team)) {
      return false;
    }

    $this->forceFill([
      'current_team_id' => $team->id,
    ])->save();

    $this->setRelation('currentTeam', $team);

    return true;
  }

  public function belongsToTeam($team): bool
  {
    if (is_null($team)) {
      return false;
    }

    return $this->teams->contains($team) || $this->ownsTeam($team);
  }

  public function ownsTeam($team): bool
  {
    if (is_null($team)) {
      return false;
    }

    return $this->id === $team->owner_id;
  }

  public function hasTeamPermission($team, $permission): bool
  {
    if ($this->ownsTeam($team)) {
      return true;
    }

    $teamMembership = $this->teams()->where('team_id', $team->id)->first();
    if (!$teamMembership) {
      return false;
    }

    $role = Role::findByName('team-' . $teamMembership->pivot->role);
    return $role->hasPermissionTo($permission);
  }

  public function getTeamRole($team): ?string
  {
    if ($this->ownsTeam($team)) {
      return 'owner';
    }

    $teamMembership = $this->teams()->where('team_id', $team->id)->first();
    return $teamMembership ? $teamMembership->pivot->role : null;
  }
}
