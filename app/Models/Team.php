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
      'team:manage',
      'team:settings:edit',
      'team:delete',
      'member:view',
      'member:invite',
      'member:remove',
      'member:role:assign',
      'billing:view',
      'billing:manage',
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
      'automation:delete'
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
    'personal_team'
  ];

  protected $casts = [
    'personal_team' => 'boolean'
  ];

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
    return $this->belongsTo(Organization::class);
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
}
