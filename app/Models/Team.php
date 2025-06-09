<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\{Model, SoftDeletes, Factories\HasFactory};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class Team extends Model
{
  use HasRoles, HasUuid, HasFactory, SoftDeletes;

  /**
   * Default role permissions mapping
   */
  public static $defaultRoles = [
    'owner' => [
      'team:create',
      'team:edit',
      'team:view',
      'team:leave',
      'team:switch',
      'team:settings:view',
      'team:settings:edit',
      'team:delete',
      'member:view',
      'member:invite',
      'member:remove',
      'member:role:assign',
      'member:role:edit',
      'member:role:view',
      'member:role:delete',
      'member:role:create',
      'member:role:revoke',
      'billing:view',
      'billing:update',
      'billing:subscribe',
      'billing:upgrade',
      'billing:downgrade',
      'billing:cancel',
      'billing:history',
      'billing:invoice:view',
      'billing:invoice:download',
      'billing:invoice:send',
      'billing:payment:method:add',
      'billing:payment:method:remove',
      'billing:payment:method:update',
      'billing:payment:method:view',
      'campaign:view',
      'campaign:create',
      'campaign:edit',
      'campaign:delete',
      'campaign:send',
      'campaign:schedule',
      'template:view',
      'template:create',
      'template:edit',
      'template:delete',
      'subscriber:view',
      'subscriber:create',
      'subscriber:edit',
      'subscriber:delete',
      'subscriber:import',
      'subscriber:export',
      'analytics:view',
      'analytics:export',
      'automation:view',
      'automation:create',
      'automation:edit',
      'automation:delete',
      'organization:view',
      'organization:edit',
      'organization:delete',
      'organization:settings:view',
      'organization:settings:edit'
    ],
    'admin' => [
      'team:settings:edit',
      'member:view',
      'member:invite',
      'campaign:view',
      'campaign:create',
      'campaign:edit',
      'campaign:send',
      'campaign:schedule',
      'template:view',
      'template:create',
      'template:edit',
      'subscriber:view',
      'subscriber:create',
      'subscriber:edit',
      'subscriber:import',
      'subscriber:export',
      'analytics:view',
      'analytics:export',
      'automation:view',
      'automation:create',
      'automation:edit'
    ],
    'member' => [
      'campaign:view',
      'campaign:create',
      'campaign:edit',
      'template:view',
      'template:create',
      'subscriber:view',
      'subscriber:create',
      'analytics:view'
    ]
  ];

  protected $fillable = [
    'uuid',
    'name',
    'owner_id',
    'organization_id',
    'personal_team'
  ];

  protected $casts = [
    'personal_team' => 'boolean'
  ];

  protected $appends = ['stats', 'recent_activities'];

  // Relationships
  public function owner(): BelongsTo
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class)
      ->withPivot('role')
      ->withTimestamps();
  }

  public function invitations(): HasMany
  {
    return $this->hasMany(InvitedTeamMember::class);
  }

  public function campaigns(): HasMany
  {
    return $this->hasMany(Campaign::class);
  }

  public function templates(): HasMany
  {
    return $this->hasMany(EmailTemplate::class);
  }

  public function subscribers(): HasMany
  {
    return $this->hasMany(Subscriber::class);
  }

  public function segments(): HasMany
  {
    return $this->hasMany(Segment::class);
  }

  public function hasReachedSubscriberLimit(): bool
  {
    $quota = $this->getQuotaSettings();
    return $this->subscribers()->count() >= ($quota['subscriber_limit'] ?? PHP_INT_MAX);
  }

  public function hasReachedCampaignLimit(): bool
  {
    $quota = $this->getQuotaSettings();
    return $this->campaigns()->count() >= ($quota['campaign_limit'] ?? PHP_INT_MAX);
  }

  public function canSendCampaigns(): bool
  {
    return !$this->hasReachedCampaignLimit() && $this->hasValidEmailSettings();
  }

  public function hasValidEmailSettings(): bool
  {
    $settings = $this->getEmailSettings();
    return !empty($settings['from_email']) && !empty($settings['from_name']);
  }

  public function updateMemberRole(User $user, string $role): void
  {
    DB::transaction(function () use ($user, $role) {
      // Update team role
      $this->users()->updateExistingPivot($user->id, ['role' => $role]);

      // Update Spatie role
      $user->syncRoles([$role === 'admin' ? 'team-admin' : 'team-member']);
    });
  }

  public function organization(): BelongsTo
  {
    return $this->belongsTo(Organization::class, 'organization_id');
  }

  // Replace existing settings methods with organization methods
  public function getBrandingSettings(): array
  {
    return $this->organization->getBrandingConfig();
  }

  public function getEmailSettings(): array
  {
    return $this->organization->getEmailConfig();
  }

  public function getQuotaSettings(): array
  {
    return $this->organization->getQuotaLimits();
  }

  public function getStatsAttribute(): array
  {
    return [
      'campaigns_count' => $this->campaigns()->count(),
      'subscribers_count' => $this->subscribers()->count(),
      'templates_count' => $this->templates()->count(),
      'members_count' => $this->users()->count(),
    ];
  }

  public function getRecentActivitiesAttribute(): array
  {
    return TrackingEvent::where('metadata->team_id', $this->id)
      ->with('trackable')
      ->latest()
      ->take(10)
      ->get()
      ->map(function ($event) {
        return [
          'id' => $event->uuid,
          'type' => $event->event_type,
          'description' => $event->description,
          'created_at' => $event->created_at,
          'user' => [
            'name' => $event->metadata['user_name'] ?? 'System',
            'avatar' => $event->metadata['user_avatar'] ?? null
          ]
        ];
      })
      ->toArray();
  }
}
