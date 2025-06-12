<?php

use App\Http\Controllers\Campaign\{
  CampaignController,
  TemplateController
};
use Illuminate\Support\Facades\Route;

// Campaigns
Route::controller(CampaignController::class)
  ->prefix('campaigns')
  ->group(function () {
    Route::get('/', 'index')->name('campaigns.index');
    Route::get('/create', 'create')->name('campaigns.create');
    Route::post('/', 'store')->name('campaigns.store');
    Route::get('/s/{campaign:uuid}', 'show')->name('campaigns.show');
    Route::get('/e/{campaign:uuid}', 'edit')->name('campaigns.edit');
    Route::put('/u/{campaign:uuid}', 'update')->name('campaigns.update');
    Route::delete('/d/{campaign:uuid}', 'destroy')->name('campaigns.destroy');
    Route::post('/send/{campaign:uuid}', 'send')->name('campaigns.send');
    Route::post('/schedule/{campaign:uuid}', 'schedule')->name('campaigns.schedule');
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
