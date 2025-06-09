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
      'team:create' => 'Create new teams',
      'team:edit' => 'Edit existing teams',
      'team:view' => 'View team details',
      'team:leave' => 'Leave the current team',
      'team:switch' => 'Switch between teams',
      'team:settings:view' => 'View team settings',
      'team:settings:edit' => 'Edit team settings',
      'team:delete' => 'Delete team',

      // Member Management
      'member:view' => 'View team members',
      'member:invite' => 'Invite new members',
      'member:remove' => 'Remove team members',
      'member:role:assign' => 'Assign roles to members',
      'member:role:revoke' => 'Revoke roles from members',
      'member:role:edit' => 'Edit member roles',
      'member:role:view' => 'View member roles',
      'member:role:delete' => 'Delete member roles',
      'member:role:create' => 'Create new member roles',

      // Billing & Subscription
      'billing:view' => 'View billing information',
      'billing:subscribe' => 'Subscribe to a plan',
      'billing:upgrade' => 'Upgrade subscription plan',
      'billing:downgrade' => 'Downgrade subscription plan',
      'billing:cancel' => 'Cancel subscription',
      'billing:update' => 'Update billing information',
      'billing:history' => 'View billing history',
      'billing:invoice:view' => 'View invoices',
      'billing:invoice:download' => 'Download invoices',
      'billing:invoice:send' => 'Send invoices to team members',
      'billing:payment:method:add' => 'Add payment methods',
      'billing:payment:method:remove' => 'Remove payment methods',
      'billing:payment:method:update' => 'Update payment methods',
      'billing:payment:method:view' => 'View payment methods',

      // Campaign Permissions
      'campaign:view' => 'View campaigns',
      'campaign:create' => 'Create new campaigns',
      'campaign:edit' => 'Edit existing campaigns',
      'campaign:delete' => 'Delete campaigns',
      'campaign:send' => 'Send campaigns',
      'campaign:schedule' => 'Schedule campaigns',
      'campaign:duplicate' => 'Duplicate campaigns',
      'campaign:archive' => 'Archive campaigns',
      'campaign:restore' => 'Restore archived campaigns',

      // Template Permissions
      'template:view' => 'View email templates',
      'template:create' => 'Create email templates',
      'template:edit' => 'Edit email templates',
      'template:delete' => 'Delete email templates',
      'template:import' => 'Import email templates',
      'template:export' => 'Export email templates',
      'template:duplicate' => 'Duplicate email templates',
      'template:archive' => 'Archive email templates',
      'template:restore' => 'Restore archived email templates',

      // Subscriber Permissions
      'subscriber:view' => 'View subscribers',
      'subscriber:create' => 'Add subscribers',
      'subscriber:edit' => 'Edit subscribers',
      'subscriber:delete' => 'Delete subscribers',
      'subscriber:import' => 'Import subscribers',
      'subscriber:export' => 'Export subscribers',
      'subscriber:segment:create' => 'Create subscriber segments',
      'subscriber:segment:edit' => 'Edit subscriber segments',
      'subscriber:segment:delete' => 'Delete subscriber segments',
      'subscriber:segment:view' => 'View subscriber segments',
      'subscriber:segment:export' => 'Export subscriber segments',
      'subscriber:segment:import' => 'Import subscriber segments',

      // Analytics Permissions
      'analytics:view' => 'View analytics',
      'analytics:export' => 'Export analytics data',
      'analytics:report:create' => 'Create analytics reports',
      'analytics:report:edit' => 'Edit analytics reports',
      'analytics:report:delete' => 'Delete analytics reports',

      // Automation Permissions
      'automation:view' => 'View automations',
      'automation:create' => 'Create automations',
      'automation:edit' => 'Edit automations',
      'automation:delete' => 'Delete automations',

      // Organization Permissions
      'organization:view' => 'View organization details',
      'organization:edit' => 'Edit organization details',
      'organization:delete' => 'Delete organization',
      'organization:settings:view' => 'View organization settings',
      'organization:settings:edit' => 'Edit organization settings',
      'organization:logo:upload' => 'Upload organization logo',
      'organization:logo:delete' => 'Delete organization logo',
      'organization:branding:view' => 'View organization branding',
      'organization:branding:edit' => 'Edit organization branding',
      'organization:email:config:view' => 'View organization email configuration',
      'organization:email:config:edit' => 'Edit organization email configuration',
      'organization:quota:view' => 'View organization quota limits',
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
