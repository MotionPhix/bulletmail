<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
  /**
   * Show the registration page.
   */
  public function create(): Response
  {
    return Inertia::render('auth/Register');
  }

  /**
   * Handle an incoming registration request.
   *
   * @throws \Illuminate\Validation\ValidationException
   */
  public function store(Request $request): RedirectResponse
  {
    $this->validateInput($request->all());

    DB::beginTransaction();

    try {
      $user = $this->createUser($request->all());
      $organization = $this->createOrganization($user, $request->all());
      $this->createTeam($user, $organization, $request->all());

      // Create free subscription
      $this->createFreeSubscription($user, $organization);

      event(new Registered($user));

      DB::commit();

      Auth::login($user);

      return redirect()->route('organizations.show', $organization);
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  protected function validateInput(array $input): void
  {
    $rules = [
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'email' => [
        'required',
        'string',
        'email:rfc,dns',
        'max:255',
        'unique:users',
        function ($attribute, $value, $fail) {
          if ($value && $this->isDisposableEmail($value)) {
            $fail('Please use a valid business email address.');
          }
        }
      ],
      'password' => [
        'required',
        'string',
        Password::defaults(function () {
          return Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised();
        })
      ],
      'password_confirmation' => ['required', 'string', 'same:password'],
      'terms' => ['accepted', 'required'],

      // Organization details
      'organization_name' => ['required', 'string', 'max:255'],
      'organization_size' => ['required', 'string', 'in:1-10,11-50,51-200,201-500,500+'],
      'industry' => ['required', 'string', 'in:technology,e-commerce,healthcare,education,finance,marketing,retail,other'],
      'website' => ['nullable', 'url'],
    ];

    Validator::make($input, $rules, [
      'organization_size.required' => 'Please provide your organization size.',
      'organization_size.in' => 'Please select a valid organization size.',
      'industry.required' => 'Please provide your industry type.',
      'industry.in' => 'Please select a valid industry.',
      'email.email' => 'Please enter a valid email address.',
      'email.unique' => 'This email is already registered.',
      'password.min' => 'Password must be at least 8 characters.',
      'terms.accepted' => 'You must accept the terms and conditions.',
    ])->validate();
  }

  protected function isDisposableEmail(string|null $email): bool
  {
    if (!$email) {
      return false;
    }

    $parts = explode('@', $email);
    if (count($parts) !== 2) {
      return false;
    }

    $disposableDomains = [
      'tempmail.com',
      'throwaway.com',
      'temp-mail.org',
      'guerrillamail.com',
      'mailinator.com',
      'yopmail.com',
      'dispostable.com',
      'sharklasers.com',
      '10minutemail.com',
      'mailnesia.com',
      'getnada.com',
      'maildrop.cc',
      'trashmail.com',
      'fakeinbox.com',
      'spamgourmet.com',
      'spambox.*',
    ];

    return in_array($parts[1], $disposableDomains);
  }

  protected function createUser(array $input): User
  {
    $user = User::create([
      'first_name' => $input['first_name'],
      'last_name' => $input['last_name'],
      'email' => $input['email'],
      'password' => Hash::make($input['password']),
      'preferences' => [
        'language' => 'en',
        'timezone' => 'UTC'
      ],
      'notification_settings' => [
        'email_notifications' => true,
        'browser_notifications' => true
      ]
    ]);

    // Assign the team-owner role to the user
    $user->assignRole('team-owner');

    return $user;
  }

  protected function createOrganization(User $user, array $input): Organization
  {
    $organization = Organization::create([
      'name' => $input['organization_name'],
      'size' => $input['organization_size'],
      'industry' => $input['industry'],
      'website' => $input['website'] ?? null,
      'default_from_name' => $user->name,
      'default_from_email' => $user->email,
      'default_reply_to' => $user->email,
      'owner_id' => $user->id,
      'settings' => [
        'billing_email' => $user->email,
        'timezone' => 'UTC',
        'date_format' => 'Y-m-d'
      ],
      'preferences' => [
        'language' => 'en',
        'notifications' => [
          'new_subscriber' => true,
          'campaign_sent' => true
        ]
      ]
    ]);

    if (isset($input['logo'])) {
      $organization->addMedia($input['logo'])
        ->toMediaCollection('logo');
    }

    return $organization;
  }

  protected function createTeam(User $user, Organization $organization, array $input): Team
  {
    $team = Team::create([
      'name' => $input['organization_name'] . ' Team',
      'owner_id' => $user->id,
      'organization_id' => $organization->id,
      'personal_team' => true
    ]);

    $user->forceFill(['current_team_id' => $team->id])->save();

    return $team;
  }

  protected function createFreeSubscription(
    User $user,
    Organization $organization
  ): void {
    $freePlan = Plan::where('slug', 'free')->firstOrFail();

    $organization->subscriptions()->create([
      'user_id' => $user->id,
      'plan_id' => $freePlan->id,
      'status' => Subscription::STATUS_ACTIVE,
      'starts_at' => now(),
      'trial_ends_at' => now()->addDays($freePlan->trial_days)
    ]);
  }
}
