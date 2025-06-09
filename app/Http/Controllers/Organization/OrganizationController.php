<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
  public function index(): Response
  {
    return Inertia::render('organization/Index', [
      'organizations' => auth()->user()->ownedOrganizations()->withCount([
        'teams',
        'teams as total_members' => fn($query) => $query->withCount('users'),
        'teams as total_subscribers' => fn($query) => $query->withCount('subscribers'),
        'teams as total_campaigns' => fn($query) => $query->withCount('campaigns'),
      ])->get(),
      'can' => [
        'create' => auth()->user()->can('create', Organization::class)
      ]
    ]);
  }

  public function show(Organization $organization): Response
  {
    return Inertia::render('organization/Show', [
      'organization' => $organization->load([
        'teams' => fn($query) => $query->withCount(['users', 'campaigns', 'subscribers']),
        'owner'
      ])->loadCount(['teams']),
      'stats' => [
        'total_members' => $organization->teams->sum('users_count'),
        'total_subscribers' => $organization->teams->sum('subscribers_count'),
        'total_campaigns' => $organization->teams->sum('campaigns_count')
      ],
      'can' => [
        'update' => auth()->user()->can('update', $organization),
        'delete' => auth()->user()->can('delete', $organization)
      ]
    ]);
  }

  public function store(StoreOrganizationRequest $request): RedirectResponse
  {
    $organization = Organization::create(array_merge(
      $request->validated(),
      [
        'owner_id' => auth()->id(),
        'settings' => [
          'billing_email' => auth()->user()->email,
        ],
        'preferences' => [
          'timezone' => 'UTC',
          'date_format' => 'Y-m-d'
        ]
      ]
    ));

    return redirect()->route('organizations.show', $organization)
      ->with('success', 'Organization created successfully.');
  }

  public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
  {
    $organization->update($request->validated());

    if ($request->hasFile('logo')) {
      $organization->addMedia($request->file('logo'))
        ->toMediaCollection('logo');
    }

    return redirect()->route('organizations.show', $organization)
      ->with('success', 'Organization updated successfully.');
  }

  public function destroy(Organization $organization): RedirectResponse
  {
    if ($organization->teams()->count() > 0) {
      return back()->with('error', 'Cannot delete organization with teams.');
    }

    $organization->delete();

    return redirect()->route('organizations.index')
      ->with('success', 'Organization deleted successfully.');
  }
}
