<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Team\TeamController as TeamTeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  return Inertia::render('Welcome');
})->name('home');

/*// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
  // Dashboard
  Route::get(
    '/dashboard',
    [DashboardController::class, 'index']
  )->name('dashboard');

  // Organization routes
  Route::controller(OrganizationController::class)
    ->prefix('organizations')
    ->middleware(['organization.access'])
    ->group(function () {
      Route::get('/', 'index')->name('organizations.index');
      Route::get('/create', 'create')->name('organizations.create');
      Route::post('/', 'store')->name('organizations.store');
      Route::get('/s/{organization:uuid}', 'show')->name('organizations.show');
      Route::put('/{organization:uuid}', 'update')->name('organizations.update');
      Route::delete('/{organization:uuid}', 'destroy')->name('organizations.destroy');
    });

  // Team routes
  Route::controller(TeamTeamController::class)
    ->prefix('teams')
    ->middleware(['team.access'])
    ->group(function () {
      Route::get('/', 'index')->name('teams.index');
      Route::get('/create', 'create')->name('teams.create');
      Route::post('/', 'store')->name('teams.store');
      Route::get('/s/{team:uuid}', 'show')->name('teams.show');
      Route::put('/{team:uuid}', 'update')->name('teams.update');
      Route::delete('/{team:uuid}', 'destroy')->name('teams.destroy');
      Route::put('/switch', 'switch')->name('teams.switch');
    });
});*/

Route::middleware(['auth', 'verified'])->group(function () {
  // Dashboard
  Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['organization.access'])
    ->name('dashboard');

  // Organization Settings (implicitly uses current organization)
  Route::middleware(['organization.access'])
    ->name('organization.')
    ->group(function () {
      require __DIR__ . '/organization/settings.php';
    });

  // Team-specific routes
  Route::middleware(['team.access'])->group(function () {
    // Teams Management
    Route::controller(TeamTeamController::class)
      ->prefix('teams')
      ->name('teams.')
      ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/store', 'store')->name('store');
        Route::get('/s/{team:uuid}', 'show')->name('show');
        Route::put('/switch', 'switch')->name('switch');
      });

    // Features (uses current team context)
    Route::prefix('app')
      ->name('app.')
      ->group(function () {
        require __DIR__ . '/team/campaigns.php';
        require __DIR__ . '/team/subscribers.php';
        require __DIR__ . '/team/automations.php';
        require __DIR__ . '/team/analytics.php';
        require __DIR__ . '/team/lists.php';
        require __DIR__ . '/team/segments.php';
      });

    // Team Settings
    Route::prefix('settings/team')
      ->name('teams.')
      ->group(function () {
        require __DIR__ . '/team/settings.php';
      });
  });
});

// Public Subscriber Actions (no auth required)
Route::prefix('s')
  ->controller(\App\Http\Controllers\SubscriberController::class)
  ->group(function () {
    Route::get('/unsubscribe/{subscriber:uuid}', 'unsubscribe')
      ->name('subscribers.unsubscribe');

    Route::get('/preferences/{subscriber:uuid}', 'preferences')
      ->name('subscribers.preferences');

    Route::put('/preferences/{subscriber:uuid}', 'updatePreferences')
      ->name('subscribers.preferences.update');
  });

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
