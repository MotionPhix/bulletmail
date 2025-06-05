<?php

namespace App\Events;

use App\Models\InvitedTeamMember;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationAccepted
{
  use Dispatchable, SerializesModels;

  public function __construct(
    public InvitedTeamMember $invitation,
    public User $acceptedBy
  ) {}
}
