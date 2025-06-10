<?php

use App\Http\Controllers\Automation\AutomationController;
use Illuminate\Support\Facades\Route;

Route::controller(AutomationController::class)
  ->prefix('automations')
  ->group(function () {
    Route::get('/', 'index')->name('automations.index');
    Route::get('/create', 'create')->name('automations.create');
    Route::post('/', 'store')->name('automations.store');
    Route::get('/{automation:uuid}', 'show')->name('automations.show');
    Route::get('/{automation:uuid}/edit', 'edit')->name('automations.edit');
    Route::put('/{automation:uuid}', 'update')->name('automations.update');
    Route::delete('/{automation:uuid}', 'destroy')->name('automations.destroy');
  });
