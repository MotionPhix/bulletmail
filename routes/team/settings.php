<?php

use App\Http\Controllers\Team\Settings\{
  GeneralController,
  MembersController,
  RolesController
};
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'settings'], function () {
  Route::get(
    '/teams',
    [GeneralController::class, 'edit']
  )->name('teams.settings');

  Route::put(
    '/teams/general',
    [GeneralController::class, 'update']
  )->name('teams.settings.general');

  // Members
  Route::get(
    '/team/members',
    [MembersController::class, 'index']
  )->name('teams.settings.members');

  Route::post(
    '/team/members/invite',
    [MembersController::class, 'invite']
  )->name('teams.settings.members.invite');

  Route::put(
    '/team/members/{user}',
    [MembersController::class, 'update']
  )->name('teams.settings.members.update');

  Route::delete(
    '/team/members/r/{user:uuid}',
    [MembersController::class, 'remove']
  )->name('teams.settings.members.remove');

  // Roles
  Route::get(
    '/team/roles',
    [RolesController::class, 'index']
  )->name('teams.settings.roles');

  Route::post(
    '/team/roles',
    [RolesController::class, 'store']
  )->name('teams.settings.roles.store');

  Route::put(
    '/team/roles/{role}',
    [RolesController::class, 'update']
  )->name('teams.settings.roles.update');

  Route::delete(
    '/team/roles/{role}',
    [RolesController::class, 'destroy']
  )->name('teams.settings.roles.destroy');
});
