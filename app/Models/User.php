<?php

namespace App\Models;

use App\Notifications\TeamInvitation;
use App\Traits\HasSubscription;
use App\Traits\HasAnalytics;
use App\Traits\HasTeams;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
  use HasApiTokens,
    HasUuid,
    HasRoles,
    HasSubscription,
    HasAnalytics,
    HasTeams,
    HasFactory,
    Notifiable,
    SoftDeletes;

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'account_status',
    'current_team_id',
    'preferences',
    'notification_settings'
  ];

  /**
   * The attributes that should be hidden for serialization.
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'preferences' => 'array',
      'notification_settings' => 'array',
      'password' => 'hashed',
    ];
  }

  /**
   * The accessors to append to the model's array form.
   */
  protected $appends = [
    'name',
    'account_status',
  ];

  // Keep existing relationships
  public function campaigns()
  {
    return $this->hasMany(Campaign::class)->latest();
  }

  public function templates()
  {
    return $this->hasMany(EmailTemplate::class);
  }

  // Team relationships
  public function ownedTeams()
  {
    return $this->hasMany(Team::class, 'owner_id');
  }

  public function teams()
  {
    return $this->belongsToMany(Team::class)
      ->withPivot('role')
      ->withTimestamps();
  }

  public function currentTeam()
  {
    return $this->belongsTo(Team::class, 'current_team_id');
  }

  public function isOrganizationOwner(Organization $organization): bool
  {
    return $this->id === $organization->owner_id;
  }

  // Subscriber relationship
  public function subscribers()
  {
    return $this->hasMany(Subscriber::class)->latest();
  }

  // Index relationships
  public function campaignStats()
  {
    return $this->hasManyThrough(CampaignStats::class, Campaign::class);
  }

  public function campaignEvents()
  {
    return $this->hasManyThrough(CampaignEvent::class, Campaign::class);
  }

  public function trackingEvents()
  {
    return $this->hasMany(TrackingEvent::class);
  }

  public function invitedTeamMembers()
  {
    return $this->hasMany(InvitedTeamMember::class);
  }

  public function teamInvitations()
  {
    return $this->hasMany(InvitedTeamMember::class, 'email', 'email');
  }

  public function sentTeamInvitations()
  {
    return $this->hasMany(InvitedTeamMember::class, 'user_id');
  }

  // Keep existing computed attributes
  public function name(): Attribute
  {
    return Attribute::get(fn() => $this->first_name . ' ' . $this->last_name);
  }

  public function accountStatus(): Attribute
  {
    return Attribute::get(fn() => '$this->account_status');
  }

  public function emailQuotaRemaining(): Attribute
  {
    return Attribute::get(function () {
      $quotaUsed = $this->trackingEvents()
        ->where('type', 'sent')
        ->where('created_at', '>=', now()->startOfMonth())
        ->count();

      return max(0, $this->email_quota - $quotaUsed);
    });
  }

  public function subscriptionStatus(): Attribute
  {
    return Attribute::get(function () {

      if ($this->isOnTrial()) {
        return 'trial';
      }

      if ($this->hasActiveSubscription()) {
        return 'active';
      }

      return 'inactive';
    });
  }

  public function hasActiveSubscription(): bool
  {
    return $this->settings->subscription_settings['plan'] !== 'free';
  }

  public function canSendEmails(): bool
  {
    return $this->hasEmailQuotaRemaining() &&
      ($this->hasActiveSubscription() || $this->isOnTrial());
  }

  public function isOnTrial(): bool
  {
    $trialEndsAt = $this->settings->subscription_settings['trial_ends_at'] ?? null;
    return $trialEndsAt && now()->parse($trialEndsAt)->isFuture();
  }

  // Keep existing methods
  #[Scope]
  public function active(Builder $query)
  {
    return $query->where('account_status', 'active');
  }

  public function hasEmailQuotaAvailable(): bool
  {
    return $this->email_quota_remaining > 0;
  }

  public function getReputationScore(): float
  {
    $totalSent = $this->trackingEvents()->where('type', 'sent')->count();
    if ($totalSent === 0) return 100.0;

    $bounces = $this->bounceLogs()->count();
    $complaints = $this->trackingEvents()->where('type', 'complaint')->count();

    $bounceRate = ($bounces / $totalSent) * 100;
    $complaintRate = ($complaints / $totalSent) * 100;

    return max(0, 100 - ($bounceRate * 2) - ($complaintRate * 5));
  }

  public function needsWarmup(): bool
  {
    return $this->created_at->isAfter(now()->subDays(30)) &&
      $this->trackingEvents()->where('type', 'sent')->count() < 1000;
  }

  public function createTeam(array|string $input): Team
  {
    $teamName = is_array($input) ? $input['organization_name'] : $input;

    $team = $this->ownedTeams()->create([
      'name' => $teamName,
      'personal_team' => true,
    ]);

    $this->current_team_id = $team->id;
    $this->save();

    return $team;
  }

  public function processTeamInvitations($team, array $members): void
  {
    foreach ($members as $member) {
      $invitation = InvitedTeamMember::invite([
        'user_id' => $this->id,
        'team_id' => $team->id,
        'email' => $member['email'],
        'role' => $member['role'],
      ]);

      // Queue the invitation email
      $invitation->notify(new TeamInvitation($this->user, $team));
    }
  }

  public function ownedOrganizations()
  {
    return $this->hasMany(Organization::class, 'owner_id');
  }

  public function organizations()
  {
    return $this->belongsToMany(Organization::class, 'team_user', 'user_id', 'organization_id');
  }

  public function belongsToTeam($team): bool
  {
    return $this->teams()
      ->where('team_id', $team->id)
      ->exists();
  }

  public function switchTeam($team): bool
  {
    if (!$this->belongsToTeam($team)) {
      return false;
    }

    $this->forceFill([
      'current_team_id' => $team->id,
    ])->save();

    return true;
  }

  // Add these methods to your existing User model

  public function subscription()
  {
    return $this->hasOne(Subscription::class)->latest();
  }

  public function subscriptions()
  {
    return $this->hasMany(Subscription::class);
  }

  public function onPaidPlan(): bool
  {
    return $this->subscription &&
      $this->subscription->isActive() &&
      !$this->subscription->plan->isFreePlan();
  }

  public function onTrial(): bool
  {
    return $this->subscription && $this->subscription->isOnTrial();
  }

  public function hasFeature(string $feature): bool
  {
    return $this->subscription && $this->subscription->hasFeature($feature);
  }

  public function getFeatureValue(string $feature, $default = null)
  {
    return $this->subscription
      ? $this->subscription->getFeatureValue($feature, $default)
      : $default;
  }
}
