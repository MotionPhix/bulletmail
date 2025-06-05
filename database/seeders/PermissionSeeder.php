<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
  public function run(): void
  {
    $permissions = [
      // Team Management
      'team:manage' => 'Manage team settings and configuration',
      'team:settings:edit' => 'Edit team settings',
      'team:delete' => 'Delete team',

      // Member Management
      'member:view' => 'View team members',
      'member:invite' => 'Invite new members',
      'member:remove' => 'Remove team members',
      'member:role:assign' => 'Assign roles to members',

      // Billing & Subscription
      'billing:view' => 'View billing information',
      'billing:manage' => 'Manage billing and subscriptions',

      // Campaign Permissions
      'campaign:view' => 'View campaigns',
      'campaign:create' => 'Create new campaigns',
      'campaign:edit' => 'Edit existing campaigns',
      'campaign:delete' => 'Delete campaigns',
      'campaign:send' => 'Send campaigns',
      'campaign:schedule' => 'Schedule campaigns',

      // Template Permissions
      'template:view' => 'View email templates',
      'template:create' => 'Create email templates',
      'template:edit' => 'Edit email templates',
      'template:delete' => 'Delete email templates',

      // Subscriber Permissions
      'subscriber:view' => 'View subscribers',
      'subscriber:create' => 'Add subscribers',
      'subscriber:edit' => 'Edit subscribers',
      'subscriber:delete' => 'Delete subscribers',
      'subscriber:import' => 'Import subscribers',
      'subscriber:export' => 'Export subscribers',

      // Analytics Permissions
      'analytics:view' => 'View analytics',
      'analytics:export' => 'Export analytics data',

      // Automation Permissions
      'automation:view' => 'View automations',
      'automation:create' => 'Create automations',
      'automation:edit' => 'Edit automations',
      'automation:delete' => 'Delete automations',
    ];

    // Create permissions
    foreach ($permissions as $name => $description) {
      Permission::create([
        'name' => $name,
        'guard_name' => 'web'
      ]);
    }

    // Create roles
    $owner = Role::create(['name' => 'team-owner', 'guard_name' => 'web']);
    $admin = Role::create(['name' => 'team-admin', 'guard_name' => 'web']);
    $member = Role::create(['name' => 'team-member', 'guard_name' => 'web']);

    // Assign permissions based on Team model's defaultRoles
    $owner->givePermissionTo(Team::$defaultRoles['owner']);
    $admin->givePermissionTo(Team::$defaultRoles['admin']);
    $member->givePermissionTo(Team::$defaultRoles['member']);
  }
}
