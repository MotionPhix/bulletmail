<?php

namespace App\Models;

use App\Notifications\TeamInvitation;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Events\TeamInvitationAccepted;
use Illuminate\Database\Eloquent\Attributes\Scope;

class InvitedTeamMember extends Model
{
  use Notifiable, HasUuid;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<string>
   */
  protected $fillable = [
    'user_id',
    'team_id',
    'email',
    'role',
    'invitation_token',
    'invited_at',
    'accepted_at',
    'expires_at',
    'last_sent_at',
    'send_count',
    'status',
    'meta'
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'invited_at' => 'datetime',
    'accepted_at' => 'datetime',
    'expires_at' => 'datetime',
    'last_sent_at' => 'datetime',
    'meta' => 'array'
  ];

  /**
   * The attributes that should be appended to arrays.
   *
   * @var array<string>
   */
  protected $appends = [
    'status_label',
    'invitation_url',
    'is_expired',
    'can_resend',
    'days_until_expiry'
  ];

  /**
   * Get the inviter/owner of the invitation.
   */
  public function inviter(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Get the team the user is invited to.
   */
  public function team(): BelongsTo
  {
    return $this->belongsTo(Team::class);
  }

  /**
   * Get the status label attribute.
   */
  protected function statusLabel(): Attribute
  {
    return Attribute::get(function () {
      if ($this->accepted_at) {
        return 'Accepted';
      }

      if ($this->is_expired) {
        return 'Expired';
      }

      return 'Pending';
    });
  }

  /**
   * Get the invitation URL attribute.
   */
  protected function invitationUrl(): Attribute
  {
    return Attribute::get(
      fn() => URL::signedRoute('team-invitations.accept', [
        'token' => $this->invitation_token
      ])
    );
  }

  /**
   * Get the is expired attribute.
   */
  protected function isExpired(): Attribute
  {
    return Attribute::get(
      fn() => $this->expires_at?->isPast() ?? false
    );
  }

  /**
   * Get the can resend attribute.
   */
  protected function canResendInvitation(): Attribute
  {
    return Attribute::get(function () {
      if ($this->accepted_at || $this->send_count >= 3) {
        return false;
      }

      return !$this->last_sent_at ||
        $this->last_sent_at->addHours(24)->isPast();
    });
  }

  /**
   * Get days until expiry attribute.
   */
  protected function daysUntilExpiry(): Attribute
  {
    return Attribute::get(function () {
      if (!$this->expires_at || $this->is_expired) {
        return 0;
      }

      return now()->diffInDays($this->expires_at);
    });
  }

  /**
   * Generate a unique invitation token.
   */
  public static function generateInvitationToken(): string
  {
    do {
      $token = Str::random(32);
    } while (static::where('invitation_token', $token)->exists());

    return $token;
  }

  /**
   * Send the team invitation notification.
   */
  public function sendInvitation(): void
  {
    $this->notify(new TeamInvitation($this->inviter, $this->team));

    $this->update([
      'last_sent_at' => now(),
      'send_count' => $this->send_count + 1
    ]);
  }

  /**
   * Accept the team invitation.
   */
  public function accept(User $user): void
  {
    DB::transaction(function () use ($user) {
      // Add user to team with the assigned role
      $this->team->users()->attach($user, ['role' => $this->role]);

      // Assign the appropriate Spatie role
      switch ($this->role) {
        case 'admin':
          $user->assignRole('team-admin');
          break;
        case 'member':
          $user->assignRole('team-member');
          break;
      }

      $this->update([
        'status' => 'accepted',
        'accepted_at' => now(),
      ]);

      event(new TeamInvitationAccepted($this, $this->team, $user));
    });
  }

  /**
   * Scope pending invitations.
   */
  #[Scope]
  public function pending(Builder $query): void
  {
    $query->whereNull('accepted_at')
      ->where('expires_at', '>', now());
  }

  /**
   * Scope expired invitations.
   */
  #[Scope]
  public function expired(Builder $query): void
  {
    $query->whereNull('accepted_at')
      ->where('expires_at', '<=', now());
  }

  /**
   * Scope accepted invitations.
   */
  #[Scope]
  public function accepted(Builder $query): void
  {
    $query->whereNotNull('accepted_at');
  }

  /**
   * Scope invitations that can be resent.
   */
  #[Scope]
  public function canResend(Builder $query): void
  {
    $query->whereNull('accepted_at')
      ->where('send_count', '<', 3)
      ->where(function ($query) {
        $query->whereNull('last_sent_at')
          ->orWhere('last_sent_at', '<=', now()->subHours(24));
      });
  }

  /**
   * Create a new invitation.
   */
  public static function invite(array $attributes): self
  {
    $invitation = static::create(array_merge($attributes, [
      'invitation_token' => static::generateInvitationToken(),
      'invited_at' => now(),
      'expires_at' => now()->addDays(7),
      'status' => 'pending',
      'send_count' => 0
    ]));

    $invitation->sendInvitation();

    return $invitation;
  }

  /**
   * Route notifications for the mail channel.
   */
  public function routeNotificationForMail(): string
  {
    return $this->email;
  }

  public function track(string $event, array $metadata = []): void
  {
    TrackingEvent::create([
      'trackable_type' => self::class,
      'trackable_id' => $this->id,
      'event_type' => $event,
      'email' => $this->email,
      'metadata' => array_merge($metadata, [
        'team_id' => $this->team_id,
        'role' => $this->role
      ])
    ]);
  }

  protected static function booted()
  {
    parent::boot();

    static::creating(function ($invitation) {
      $invitation->expires_at = $invitation->expires_at ?? now()->addDays(7);
      $invitation->status = $invitation->status ?? 'pending';
      $invitation->send_count = $invitation->send_count ?? 0;
    });

    static::created(function ($invitation) {
      $invitation->track('invitation_created');
    });

    static::updated(function ($invitation) {
      if ($invitation->isDirty('status')) {
        $invitation->track('invitation_' . $invitation->status);
      }
    });
  }
}
