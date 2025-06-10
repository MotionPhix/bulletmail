<?php

use App\Http\Controllers\Team\Settings\{
  GeneralController,
  MembersController,
  RolesController
};
use Illuminate\Support\Facades\Route;

Route::name('settings.')
  ->group(function () {
    Route::get(
      '/general',
      [GeneralController::class, 'edit']
    )->name('general.edit');

    Route::put(
      '/general',
      [GeneralController::class, 'update']
    )->name('general.update');

    // Members
    Route::get(
      '/members',
      [MembersController::class, 'index']
    )->name('members.index');

    Route::post(
      '/members/invite',
      [MembersController::class, 'invite']
    )->name('members.invite');

    Route::put(
      '/members/{user}',
      [MembersController::class, 'update']
    )->name('members.update');

    Route::delete(
      '/members/r/{user:uuid}',
      [MembersController::class, 'remove']
    )->name('members.remove');

    // Roles
    Route::get(
      '/roles',
      [RolesController::class, 'index']
    )->name('roles.index');

    Route::post(
      '/roles',
      [RolesController::class, 'store']
    )->name('roles.store');

    Route::put(
      '/roles/{role}',
      [RolesController::class, 'update']
    )->name('roles.update');

    Route::delete(
      '/roles/{role}',
      [RolesController::class, 'destroy']
    )->name('roles.destroy');
  });
