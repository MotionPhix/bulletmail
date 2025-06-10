<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
  public function run(): void
  {
    // Free Plan
    Plan::create([
      'name' => 'Free',
      'slug' => 'free',
      'price' => 0,
      'trial_days' => 0,
      'is_active' => true,
      'features' => [
        // Team Features
        'team_limit' => 1,
        'members_per_team' => 2,

        // Email Features
        'campaign_limit' => 2,
        'subscriber_limit' => 500,
        'monthly_email_limit' => 2000,
        'daily_email_limit' => 200,

        // Template Features
        'template_limit' => 5,
        'custom_templates' => false,
        'template_sharing' => false,

        // Campaign Features
        'can_schedule_campaigns' => false,
        'ab_testing' => false,
        'advanced_analytics' => false,

        // Subscriber Features
        'can_import_subscribers' => true,
        'can_export_subscribers' => false,
        'segments_limit' => 2,

        // Support & Services
        'support_type' => 'community',
        'premium_email_services' => false,
        'custom_branding' => false
      ]
    ]);

    // Pro Plan
    Plan::create([
      'name' => 'Pro',
      'slug' => 'pro',
      'price' => 2999,
      'trial_days' => 14,
      'is_active' => true,
      'is_featured' => true,
      'features' => [
        // Team Features
        'team_limit' => 3,
        'members_per_team' => 10,

        // Email Features
        'campaign_limit' => 50,
        'subscriber_limit' => 10000,
        'monthly_email_limit' => 50000,
        'daily_email_limit' => 5000,

        // Template Features
        'template_limit' => 50,
        'custom_templates' => true,
        'template_sharing' => true,

        // Campaign Features
        'can_schedule_campaigns' => true,
        'ab_testing' => true,
        'advanced_analytics' => true,

        // Subscriber Features
        'can_import_subscribers' => true,
        'can_export_subscribers' => true,
        'segments_limit' => 20,

        // Support & Services
        'support_type' => 'priority',
        'premium_email_services' => true,
        'custom_branding' => true
      ]
    ]);
  }
}
