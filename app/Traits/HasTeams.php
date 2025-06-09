<?php

namespace App\Traits;

use App\Models\{Team, Organization};
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany, BelongsTo};
use Illuminate\Support\Collection;

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

  public function currentTeam(): BelongsTo
  {
    return $this->belongsTo(Team::class, 'current_team_id');
  }

  public function organizations(): HasMany
  {
    return $this->hasMany(Organization::class, 'owner_id');
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

    $this->forceFill(['current_team_id' => $team->id])->save();
    $this->setRelation('currentTeam', $team);

    return true;
  }

  public function belongsToTeam($team): bool
  {
    return $this->teams->contains($team) || $this->ownsTeam($team);
  }

  public function ownsTeam($team): bool
  {
    return $this->id === $team->owner_id;
  }

  public function hasTeamRole($team, $role): bool
  {
    if ($this->ownsTeam($team)) {
      return true;
    }

    return $this->teams->contains(function ($t) use ($team, $role) {
      return $t->id === $team->id && $t->pivot->role === $role;
    });
  }
}
