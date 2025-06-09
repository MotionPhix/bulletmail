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
      'features' => [
        'campaign_limit' => 2,
        'subscriber_limit' => 500,
        'monthly_email_limit' => 2000,
        'daily_email_limit' => 200,
        'can_schedule_campaigns' => false,
        'can_use_templates' => true,
        'can_import_subscribers' => true,
        'support_type' => 'community',
        'premium_email_services' => false
      ]
    ]);

    // Pro Plan
    Plan::create([
      'name' => 'Pro',
      'slug' => 'pro',
      'price' => 2999,
      'features' => [
        'campaign_limit' => 50,
        'subscriber_limit' => 10000,
        'monthly_email_limit' => 50000,
        'daily_email_limit' => 5000,
        'can_schedule_campaigns' => true,
        'can_use_templates' => true,
        'can_import_subscribers' => true,
        'can_export_data' => true,
        'support_type' => 'priority',
        'premium_email_services' => true
      ]
    ]);
  }
}
