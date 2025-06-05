<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\EmailTemplate;
use App\Models\Campaign;
use App\Models\Subscriber;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
  public function run(): void
  {
    // Create owner user first
    $owner = User::factory()->create([
      'email' => 'test@example.com',
      'first_name' => 'Test',
      'last_name' => 'User',
      'organization_name' => 'Acme Corp',
      'organization_size' => '11-50',
      'industry' => 'technology',
      'website' => 'https://www.acmecorp.com',
      'onboarding_completed_at' => now(),
    ]);

    // Create team with owner
    $team = Team::factory()
      ->withOwner($owner)
      ->create([
        'name' => $owner->organization_name,
        'personal_team' => true,
      ]);

    // Set current team
    $owner->update(['current_team_id' => $team->id]);

    // Create additional team members
    User::factory()
      ->count(2)
      ->sequence(
        [
          'organization_name' => 'Marketing Team',
          'organization_size' => '1-10',
          'industry' => 'marketing',
        ],
        [
          'organization_name' => 'Sales Team',
          'organization_size' => '11-50',
          'industry' => 'sales',
        ]
      )
      ->create()
      ->each(function ($user) use ($team) {
        $team->users()->attach($user, ['role' => 'member']);
      });

    // Create templates
    EmailTemplate::factory()
      ->count(5)
      ->sequence(
        ['category' => 'newsletter'],
        ['category' => 'marketing'],
        ['category' => 'transactional']
      )
      ->create([
        'team_id' => $team->id,
        'user_id' => $owner->id
      ]);

    // Create campaigns
    Campaign::factory()
      ->count(3)
      ->sequence(
        ['status' => 'draft'],
        ['status' => 'scheduled'],
        ['status' => 'sent']
      )
      ->create([
        'team_id' => $team->id,
        'user_id' => $owner->id
      ]);

    // Create subscribers
    Subscriber::factory()
      ->count(100)
      ->sequence(
        ['status' => 'subscribed'],
        ['status' => 'unsubscribed']
      )
      ->create([
        'team_id' => $team->id,
        'user_id' => $owner->id
      ]);
  }
}
