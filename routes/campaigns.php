<?php

use App\Http\Controllers\Campaign\{
  CampaignController,
  TemplateController
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'team.access'])->group(function () {
  // Campaigns
  Route::controller(CampaignController::class)
    ->prefix('campaigns')
    ->group(function () {
      Route::get('/', 'index')->name('campaigns.index');
      Route::get('/create', 'create')->name('campaigns.create');
      Route::post('/', 'store')->name('campaigns.store');
      Route::get('/{campaign:uuid}', 'show')->name('campaigns.show');
      Route::get('/{campaign:uuid}/edit', 'edit')->name('campaigns.edit');
      Route::put('/{campaign:uuid}', 'update')->name('campaigns.update');
      Route::delete('/{campaign:uuid}', 'destroy')->name('campaigns.destroy');
      Route::post('/{campaign:uuid}/send', 'send')->name('campaigns.send');
      Route::post('/{campaign:uuid}/schedule', 'schedule')->name('campaigns.schedule');
    });

  // Templates
  Route::controller(TemplateController::class)
    ->prefix('templates')
    ->group(function () {
      Route::get('/', 'index')->name('templates.index');
      Route::get('/create', 'create')->name('templates.create');
      Route::post('/', 'store')->name('templates.store');
      Route::get('/{template:uuid}/edit', 'edit')->name('templates.edit');
      Route::put('/{template:uuid}', 'update')->name('templates.update');
      Route::delete('/{template:uuid}', 'destroy')->name('templates.destroy');
    });
});
