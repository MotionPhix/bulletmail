<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTeamScope
{
  protected static function bootHasTeamScope(): void
  {
    static::creating(function ($model) {
      if (!$model->team_id && auth()->check()) {
        $model->team_id = auth()->user()->currentTeam->id;
      }
    });
  }

  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  public function scopeForTeam(Builder $query, $team): Builder
  {
    return $query->where('team_id', is_numeric($team) ? $team : $team->id);
  }

  public function scopeForCurrentTeam(Builder $query): Builder
  {
    return $query->where('team_id', auth()->user()->currentTeam->id);
  }
}
