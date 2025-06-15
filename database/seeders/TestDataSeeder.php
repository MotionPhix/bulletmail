<?php

namespace Database\Seeders;

use App\Models\{Organization, Team, User, EmailTemplate, Campaign, Subscriber, Plan, Subscription};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
  public function run(): void
  {
    // Get plans
    $freePlan = Plan::where('slug', 'free')->first();
    $proPlan = Plan::where('slug', 'pro')->first();

    // Create owner user
    $owner = User::factory()->create([
      'email' => 'test@example.com',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    // Create organization
    $organization = Organization::factory()
      ->withOwner($owner)
      ->create([
        'name' => 'Acme Corp',
        'size' => '11-50',
        'industry' => 'technology',
        'website' => 'https://www.acmecorp.com',
        'default_from_name' => 'Acme Corp',
        'default_from_email' => 'no-reply@acmecorp.com',
        'default_reply_to' => 'support@acmecorp.com',
      ]);

    // Create pro subscription
    Subscription::create([
      'organization_id' => $organization->id,
      'user_id' => $owner->id,
      'plan_id' => $proPlan->id,
      'status' => 'active',
      'starts_at' => now(),
      'trial_ends_at' => now()->addDays(14)
    ]);

    $team = $organization->teams()->first();
    $owner->forceFill(['current_team_id' => $team->id])->save();
    $owner->assignRole('team-owner');

    // Create team members
    collect(range(1, 2))->each(function () use ($team, $freePlan) {
      $member = User::factory()->create();
      $team->users()->attach($member, ['role' => 'member']);
      $member->assignRole('team-member');

      Subscription::create([
        'organization_id' => $team->organization_id,
        'user_id' => $member->id,
        'plan_id' => $freePlan->id,
        'status' => 'active',
        'starts_at' => now()
      ]);
    });

    // Create resources in chunks
    $this->createTemplates($team, $owner);
    $this->createCampaigns($team, $owner);
    $this->createSubscribers($team);
  }

  private function createTemplates($team, $owner): void
  {
    collect(range(1, 5))->each(
      fn($i) => EmailTemplate::factory()
        ->forTeam($team)
        ->create([
          'status' => match ($i) {
            1 => 'draft',
            2 => 'published',
            3 => 'archived',
            4 => 'deleted',
          },
          'category' => match ($i % 3) {
            0 => 'newsletter',
            1 => 'marketing',
            2 => 'transactional',
            3 => 'announcement',
            4 => 'onboarding',
            5 => 'notification',
          }
        ])
    );
  }

  private function createCampaigns($team, $owner): void
  {
    collect(range(1, 3))->each(
      fn($i) => Campaign::factory()
        ->forTeam($team)
        ->create([
          'status' => match ($i) {
            1 => 'draft',
            2 => 'scheduled',
            3 => 'sent',
            4 => 'archived',
            5 => 'cancelled',
            6 => 'sending',
            7 => 'paused',
            8 => 'failed',
            9 => 'deleted',
            10 => 'completed'
          }
        ])
    );
  }

  private function createSubscribers($team)
  {
    collect(range(1, 100))
      ->chunk(20)
      ->each(
        fn($chunk) => Subscriber::factory()
          ->count($chunk->count())
          ->forTeam($team)
          ->create([
            'status' => fake()->randomElement(['subscribed', 'unsubscribed'])
          ])
      );
  }
}
