<?php

namespace App\Http\Controllers;

use App\Models\InvitedTeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Jetstream\Jetstream;

class TeamInvitationController extends Controller
{
  public function accept(Request $request, string $token)
  {
    $invitation = InvitedTeamMember::where('invitation_token', $token)
      ->whereNull('accepted_at')
      ->firstOrFail();

    if ($invitation->created_at->addDays(7)->isPast()) {
      return redirect()->route('login')
        ->with('error', 'This invitation has expired.');
    }

    // If user is logged in
    if (Auth::check()) {
      $user = Auth::user();

      // Add user to team
      $invitation->team->users()->attach($user, ['role' => $invitation->role]);

      $invitation->update([
        'accepted_at' => now()
      ]);

      return redirect()->route('dashboard')
        ->with('success', 'You have joined the team successfully.');
    }

    // If user needs to register
    if (!User::where('email', $invitation->email)->exists()) {
      return inertia('Auth/AcceptInvitation', [
        'token' => $token,
        'email' => $invitation->email,
        'team' => $invitation->team,
        'inviter' => $invitation->user
      ]);
    }

    return redirect()->route('login')
      ->with('status', 'Please login to accept the invitation.');
  }

  public function register(Request $request, string $token)
  {
    $invitation = InvitedTeamMember::where('invitation_token', $token)
      ->whereNull('accepted_at')
      ->firstOrFail();

    $request->validate([
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'password' => ['required', 'string', 'min:8', 'confirmed'],
      'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
    ]);

    $user = User::create([
      'first_name' => $request->first_name,
      'last_name' => $request->last_name,
      'email' => $invitation->email,
      'password' => Hash::make($request->password),
      'email_verified_at' => now(), // Auto verify since they got the invitation
    ]);

    // Add user to team
    $invitation->team->users()->attach($user, ['role' => $invitation->role]);

    $invitation->update([
      'accepted_at' => now()
    ]);

    Auth::login($user);

    return redirect()->route('dashboard')
      ->with('success', 'Welcome! You have joined the team successfully.');
  }
}
