<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
  ->group(function () {

    /*Route::get('/user', function (Request $request) {
      return $request->user();
    });

    Route::get('/teams', function (Request $request) {
      return $request->user()->allTeams();
    });

    Route::get('/teams/{team}', function (Request $request, $team) {
      return $request->user()->currentTeam;
    });

    Route::get('/teams/{team}/members', function (Request $request, $team) {
      return $request->user()->currentTeam->users()->withPivot('role')->get();
    });

    Route::post('/teams/{team}/members', function (Request $request, $team) {
      $user = $request->user();
      $team = $user->currentTeam;

      $team->users()->attach($request->input('user_id'), ['role' => $request->input('role')]);

      return response()->json(['message' => 'Member added successfully.']);
    });

    Route::delete('/teams/{team}/members/{user}', function (Request $request, $team, $user) {
      $team = $request->user()->currentTeam;

      if ($team->owner_id === $user) {
        return response()->json(['message' => 'Cannot remove team owner.'], 403);
      }

      $team->users()->detach($user);

      return response()->json(['message' => 'Member removed successfully.']);
    });

    Route::put('/teams/{team}/members/{user}', function (Request $request, $team, $user) {
      $team = $request->user()->currentTeam;

      $team->users()->updateExistingPivot($user, ['role' => $request->input('role')]);

      return response()->json(['message' => 'Member role updated successfully.']);
    });

    Route::post('/teams/{team}/invite', function (Request $request, $team) {
      $team = $request->user()->currentTeam;

      $invitation = $team->invitations()->create([
        'email' => $request->input('email'),
        'role' => $request->input('role'),
        'user_id' => $request->user()->id
      ]);

      // Notify the invited user (not implemented here)
      // $invitation->notify(new TeamInvitation($team));

      return response()->json(['message' => 'Invitation sent successfully.']);
    });

    Route::get('/teams/{team}/invitations', function (Request $request, $team) {
      return $request->user()->currentTeam->invitations()->pending()->get();
    });

    Route::get('/teams/{team}/campaigns', function (Request $request, $team) {
      return $request->user()->currentTeam->campaigns()->with(['stats', 'user:id,name,email'])->get();
    });

    Route::post('/teams/{team}/campaigns', [\App\Http\Controllers\Campaign\CampaignController::class, 'store'])
      ->name('campaigns.store');

    Route::get('/teams/{team}/campaigns/{campaign:uuid}', [\App\Http\Controllers\Campaign\CampaignController::class, 'show'])
      ->name('campaigns.show');

    Route::put('/teams/{team}/campaigns/{campaign:uuid}', [\App\Http\Controllers\Campaign\CampaignController::class, 'update'])
      ->name('campaigns.update');

    Route::delete('/teams/{team}/campaigns/{campaign:uuid}', [\App\Http\Controllers\Campaign\CampaignController::class, 'destroy'])
      ->name('campaigns.destroy');

    Route::post('/teams/{team}/campaigns/{campaign:uuid}/send', [\App\Http\Controllers\Campaign\CampaignController::class, 'send'])
      ->name('campaigns.send');

    Route::post('/teams/{team}/campaigns/{campaign:uuid}/schedule', [\App\Http\Controllers\Campaign\CampaignController::class, 'schedule'])
      ->name('campaigns.schedule');*/

    Route::prefix('templates')
      ->controller(\App\Http\Controllers\Api\Campaign\TemplateController::class)
      ->group(function () {

      Route::get('/', 'index')
        ->name('api.templates.index');

      Route::post('/', 'store')
        ->name('api.templates.store');

      Route::get('/s/{template}', 'show')
        ->name('api.templates.show');

        Route::get('/e/{template:uuid}', 'edit')
          ->name('api.templates.edit');

      Route::put('/u/{template:uuid}', 'update')
        ->name('api.templates.update');

      Route::delete('/d/{template:uuid}', 'destroy')
        ->name('api.templates.destroy');

    });

    /*Route::get('/teams/{team}/analytics', function (Request $request, $team) {
      return $request->user()->currentTeam->analytics()->get();
    });

    Route::get('/teams/{team}/automation', function (Request $request, $team) {
      return $request->user()->currentTeam->automations()->get();
    });
    Route::post('/teams/{team}/automation', [\App\Http\Controllers\Automation\AutomationController::class, 'store'])
      ->name('automation.store');
    Route::put('/teams/{team}/automation/{automation:uuid}', [\App\Http\Controllers\Automation\AutomationController::class, 'update'])
      ->name('automation.update');
    Route::delete('/teams/{team}/automation/{automation:uuid}', [\App\Http\Controllers\Automation\AutomationController::class, 'destroy'])
      ->name('automation.destroy');
    Route::get('/teams/{team}/mailing-lists', function (Request $request, $team) {
      return $request->user()->currentTeam->mailingLists()->select('id', 'name')->get();
    });

    Route::post('/teams/{team}/mailing-lists', [\App\Http\Controllers\MailingList\MailingListController::class, 'store'])
      ->name('mailing-lists.store');

    Route::get('/teams/{team}/mailing-lists/{list:uuid}', [\App\Http\Controllers\MailingList\MailingListController::class, 'show'])
      ->name('mailing-lists.show');
    Route::put('/teams/{team}/mailing-lists/{list:uuid}', [\App\Http\Controllers\MailingList\MailingListController::class, 'update'])
      ->name('mailing-lists.update');

    Route::delete('/teams/{team}/mailing-lists/{list:uuid}', [\App\Http\Controllers\MailingList\MailingListController::class, 'destroy'])
      ->name('mailing-lists.destroy');
    Route::post('/teams/{team}/mailing-lists/{list:uuid}/subscribers', [\App\Http\Controllers\MailingList\SubscriberController::class, 'store'])
      ->name('subscribers.store');*/

  });
