<?php

namespace App\Policies;

use App\Models\{User, Subscriber};
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriberPolicy
{
  use HandlesAuthorization;

  public function viewAny(User $user): bool
  {
    return true;
  }

  public function view(User $user, Subscriber $subscriber): bool
  {
    return $user->currentTeam->id === $subscriber->team_id;
  }

  public function create(User $user): bool
  {
    return true;
  }

  public function update(User $user, Subscriber $subscriber): bool
  {
    return $user->currentTeam->id === $subscriber->team_id;
  }

  public function delete(User $user, Subscriber $subscriber): bool
  {
    return $user->currentTeam->id === $subscriber->team_id;
  }

  public function bulkDelete(User $user): bool
  {
    return true;
  }

  public function import(User $user): bool
  {
    return true;
  }

  public function export(User $user): bool
  {
    return true;
  }
}
