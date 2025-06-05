<?php

namespace App\Models;

use App\Notifications\TeamInvitation;
use App\Traits\HasSubscription;
use App\Traits\HasEmailQuota;
use App\Traits\HasBranding;
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
    HasEmailQuota,
    HasBranding,
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
    'organization_name',
    'organization_size',
    'industry',
    'website',
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
      'onboarding_completed_at' => 'datetime',
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

  // Subscriber relationship
  public function subscribers()
  {
    return $this->hasMany(Subscriber::class)->latest();
  }

  // Settings relationship
  public function settings()
  {
    return $this->hasOne(Setting::class)->withDefault([
      'preferences' => [
        'language' => 'en',
        'timezone' => 'UTC',
      ],
      'notification_settings' => [
        'email_notifications' => true,
        'in_app_notifications' => true,
      ],
      'email_settings' => [
        'from_name' => null,
        'reply_to' => null,
      ],
      'branding_settings' => [
        'logo_url' => null,
        'primary_color' => '#4F46E5',
        'accent_color' => '#818CF8',
      ],
    ]);
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

  public function onboardingProgress()
  {
    return $this->hasOne(OnboardingProgress::class);
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

  public function createTeam(array $input): mixed
  {
    $team = $this->ownedTeams()->create([
      'name' => $input['organization_name'],
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
}
