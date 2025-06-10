<?php

use App\Http\Controllers\Organization\Settings\{
  GeneralController,
  BrandingController,
  BillingController,
  IntegrationsController
};
use Illuminate\Support\Facades\Route;

Route::prefix('settings')
  ->name('settings.')
  ->group(function () {
    // General Settings
    Route::controller(GeneralController::class)->group(function () {
      Route::get('/general', 'edit')->name('general.edit');
      Route::put('/general/{organization:uuid}', 'update')->name('general.update');
    });

    // Branding Settings
    Route::controller(BrandingController::class)
      ->prefix('branding')
      ->group(function () {
        Route::get('/', 'edit')->name('branding.edit');
        Route::put('/{organization:uuid}', 'update')->name('branding.update');
        Route::delete('/d/logo/{organization:uuid}', 'deleteLogo')->name('branding.logo.delete');
        Route::post('/r/logo/{organization:uuid}', 'regenerateConversions')->name('branding.logo.regenerate');
      });

    // Billing Settings
    Route::get('/billing', [BillingController::class, 'edit'])->name('billing');
    Route::post('/billing/subscribe/{plan:uuid}', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');

    // Integrations Settings
    Route::get('/integrations', [IntegrationsController::class, 'edit'])->name('integrations');
    Route::put('/integrations/{organization:uuid}', [IntegrationsController::class, 'update'])->name('integrations.update');
  });
