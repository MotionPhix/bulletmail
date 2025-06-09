<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Team\TeamController as TeamTeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
  return Inertia::render('Welcome');
})->name('home');

// Protected routes
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
});

require __DIR__ . '/organization/settings.php';
require __DIR__ . '/team/settings.php';
require __DIR__ . '/campaigns.php';
require __DIR__ . '/subscribers.php';
require __DIR__ . '/automations.php';
require __DIR__ . '/analytics.php';
require __DIR__ . '/settings.php';
require __DIR__ . '/lists.php';
require __DIR__ . '/segments.php';
require __DIR__ . '/auth.php';
