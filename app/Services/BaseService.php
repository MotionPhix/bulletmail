<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogger;

abstract class BaseService
{
  protected function transaction(callable $callback)
  {
    return DB::transaction($callback);
  }

  protected function logActivity(Model $model, string $description, array $properties = []): void
  {
    ActivityLogger::log(
      $model,
      auth()->user(),
      $description,
      $properties
    );
  }

  protected function validateTeamAccess(Model $model): void
  {
    if ($model->team_id !== auth()->user()->currentTeam->id) {
      throw new \Exception('Unauthorized access to team resource.');
    }
  }
}
