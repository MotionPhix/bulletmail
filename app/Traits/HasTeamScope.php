<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasTeamScope
{
  protected static function bootHasTeamScope()
  {
    static::addGlobalScope('team', function ($query) {
      if (auth()->check()) {
        $query->where('team_id', auth()->user()->current_team_id);
      }
    });

    static::creating(function ($model) {
      if (auth()->check() && !$model->team_id) {
        $model->team_id = auth()->user()->current_team_id;
      }
    });
  }
}
