<?php

namespace App\Events;

use App\Models\InvitedTeamMember;
use App\Models\Team;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationAccepted
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public function __construct(
    public readonly InvitedTeamMember $invitation,
    public readonly Team $team,
    public readonly User $user
  ) {}
}
