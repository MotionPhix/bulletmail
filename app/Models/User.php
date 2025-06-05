<?php

namespace App\Models;

use App\Traits\HasSubscription;
use App\Traits\HasEmailQuota;
use App\Traits\HasBranding;
use App\Traits\HasAnalytics;
use App\Traits\HasTeams;
use App\Traits\HasRegistrationSteps;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
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
    HasRegistrationSteps,
    HasFactory,
    HasProfilePhoto,
    Notifiable,
    TwoFactorAuthenticatable,
    SoftDeletes;

  /**
   * The attributes that are mass assignable.
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'completed_registration_steps',
    'account_status',
    'last_login_at',
    'last_login_ip',
  ];

  /**
   * The attributes that should be hidden for serialization.
   */
  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];

  /**
   * The attributes that should be cast.
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'completed_registration_steps' => 'array',
      'last_login_at' => 'datetime',
    ];
  }

  /**
   * The accessors to append to the model's array form.
   */
  protected $appends = [
    'profile_photo_url',
    'name',
    'account_status',
    'registration_progress'
  ];

  // Update registration step constants
  public const REGISTRATION_STEP_ACCOUNT = 'account';
  public const REGISTRATION_STEP_ORGANIZATION = 'organization';
  public const REGISTRATION_STEP_TEAM = 'team';
  public const REGISTRATION_STEP_VERIFICATION = 'verification';

  public static function getRegistrationSteps(): array
  {
    return [
      self::REGISTRATION_STEP_ACCOUNT => [
        'title' => 'Account',
        'description' => 'Create your account'
      ],
      self::REGISTRATION_STEP_ORGANIZATION => [
        'title' => 'Organization',
        'description' => 'Tell us about your organization'
      ],
      self::REGISTRATION_STEP_TEAM => [
        'title' => 'Team',
        'description' => 'Invite your team members'
      ],
      self::REGISTRATION_STEP_VERIFICATION => [
        'title' => 'Verification',
        'description' => 'Verify your email address'
      ]
    ];
  }

  // Keep existing relationships
  public function campaigns()
  {
    return $this->hasMany(Campaign::class)->latest();
  }

  public function templates()
  {
    return $this->hasMany(EmailTemplate::class);
  }

  public function registrationData()
  {
    return $this->hasMany(RegistrationData::class);
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

  public function isTrialStatus(): Attribute
  {
    return Attribute::get(function () {
      $trialEndsAt = $this->settings->subscription_settings['trial_ends_at'] ?? null;
      return $trialEndsAt && now()->parse($trialEndsAt)->isFuture();
    });
  }

  public function subscriptionStatus(): Attribute
  {
    return Attribute::get(function () {
      $trialEndsAt = $this->settings->subscription_settings['trial_ends_at'] ?? null;
      $isTrialActive = $trialEndsAt && now()->parse($trialEndsAt)->isFuture();

      if ($isTrialActive) {
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
    $trialEndsAt = $this->settings->subscription_settings['trial_ends_at'] ?? null;
    $isTrialActive = $trialEndsAt && now()->parse($trialEndsAt)->isFuture();

    return $this->registration_status === 'completed' &&
      $this->hasEmailQuotaRemaining() &&
      ($this->hasActiveSubscription() || $isTrialActive);
  }

  // Add new registration-related computed attribute
  public function registrationProgress(): Attribute
  {
    return Attribute::get(function () {
      $steps = array_keys(self::getRegistrationSteps());
      $completedSteps = $this->completed_registration_steps ?? [];
      $currentStep = $this->getCurrentRegistrationStep();

      return [
        'current_step' => $currentStep,
        'current_step_index' => array_search($currentStep, $steps),
        'completed_steps' => $completedSteps,
        'total_steps' => count($steps),
        'percentage' => count($steps) > 0
          ? (count($completedSteps) / count($steps)) * 100
          : 0,
      ];
    });
  }

  public function isOnTrial(): bool
  {
    $trialEndsAt = $this->settings->subscription_settings['trial_ends_at'] ?? null;
    return $trialEndsAt && now()->parse($trialEndsAt)->isFuture();
  }

  // Keep existing methods
  public function scopeActive($query)
  {
    return $query->where('account_status', 'active');
  }

  public function recordLogin(string $ip)
  {
    $this->update([
      'last_login_at' => now(),
      'last_login_ip' => $ip
    ]);
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

  // Add registration-specific methods
  public function hasCompletedStep(string $step): bool
  {
    return in_array($step, $this->completed_registration_steps ?? []);
  }

  public function getCurrentRegistrationStep(): string
  {
    $steps = array_keys(self::getRegistrationSteps());
    $completedSteps = $this->completed_registration_steps ?? [];

    foreach ($steps as $step) {
      if (!in_array($step, $completedSteps)) {
        return $step;
      }
    }

    return end($steps);
  }

  public function completeRegistrationStep(string $step, array $data = []): void
  {
    $this->registrationData()->create([
      'step' => $step,
      'data' => $data,
      'completed_at' => now(),
    ]);

    $completedSteps = $this->completed_registration_steps ?? [];
    $completedSteps[] = $step;
    $this->completed_registration_steps = array_unique($completedSteps);

    if ($step === self::REGISTRATION_STEP_VERIFICATION) {
      $this->registration_status = 'completed';
      $this->registration_completed_at = now();

      // Set trial period
      $this->settings->update([
        'subscription_settings' => array_merge(
          $this->settings->subscription_settings ?? [],
          ['trial_ends_at' => now()->addDays(14)->format('Y-m-d H:i:s')]
        )
      ]);
    }

    $this->save();
  }
}
