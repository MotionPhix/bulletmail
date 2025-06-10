<?php

namespace App\Http\Controllers\Organization\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
  protected function getOrganization()
  {
    return auth()->user()->currentTeam->organization;
  }

  /**
   * Show the billing settings page.
   *
   * @return \Inertia\Response
   */
  public function edit(): Response
  {
    $organization = $this->getOrganization();
    $currentSubscription = $organization->subscription;
    $currentPlan = $currentSubscription?->plan;

    return Inertia::render('organization/settings/Billing', [
      'organization' => $organization,
      'currentPlan' => $currentPlan,
      'subscription' => $currentSubscription?->only([
        'status',
        'trial_ends_at',
        'ends_at',
        'cancelled_at'
      ]),
      'availablePlans' => Plan::query()
        ->orderBy('sort_order')
        ->get()
        ->map(fn($plan) => [
          'id' => $plan->id,
          'uuid' => $plan->uuid,
          'name' => $plan->name,
          'description' => $plan->description,
          'price' => $plan->price,
          'trial_days' => $plan->trial_days,
          'features' => $plan->features,
          'is_current' => $currentPlan?->id === $plan->id,
        ])
    ]);
  }

  public function subscribe(
    Plan $plan
  ): RedirectResponse {
    try {
      DB::beginTransaction();

      $organization = $this->getOrganization();

      // Cancel current subscription if exists
      if ($currentSub = $organization->subscription) {
        $currentSub->update([
          'status' => Subscription::STATUS_CANCELLED,
          'ends_at' => now()
        ]);
      }

      // Create new subscription
      $organization->subscriptions()->create([
        'user_id' => auth()->id(),
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'starts_at' => now(),
        'trial_ends_at' => !$plan->isFreePlan() ? now()->addDays($plan->trial_days) : null
      ]);

      DB::commit();

      return back()->with('success', 'Successfully switched to ' . $plan->name . ' plan.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Unable to update subscription. Please try again.');
    }
  }

  public function cancel(): RedirectResponse
  {
    try {
      DB::beginTransaction();

      $organization = $this->getOrganization();

      $currentSub = $organization->subscription;

      if (!$currentSub || $currentSub->status === Subscription::STATUS_CANCELLED) {
        return back()->with('error', 'No active subscription found.');
      }

      // Cancel current subscription
      $currentSub->update([
        'status' => Subscription::STATUS_CANCELLED,
        'cancelled_at' => now(),
        'ends_at' => now()->addDays(30) // Grace period
      ]);

      // Create new free plan subscription starting after grace period
      $freePlan = Plan::where('slug', 'free')->firstOrFail();

      $organization->subscriptions()->create([
        'user_id' => auth()->id(),
        'plan_id' => $freePlan->id,
        'status' => Subscription::STATUS_PENDING,
        'starts_at' => now()->addDays(30),
      ]);

      DB::commit();

      return back()->with('success', 'Subscription cancelled successfully. Your service will continue until the end of the billing period.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Unable to cancel subscription. Please try again.');
    }
  }
}
