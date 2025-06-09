<?php

use App\Http\Controllers\Organization\Settings\{
  GeneralController,
  BrandingController,
  BillingController,
  IntegrationsController
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'organization.access'])
  ->prefix('organizations/{organization:uuid}/settings')
  ->name('organization.settings.')
  ->group(function () {
    // General Settings
    Route::get('/', [GeneralController::class, 'edit'])->name('general');
    Route::put('/', [GeneralController::class, 'update']);

    // Branding Settings
    Route::controller(BrandingController::class)->group(function () {
      Route::get('/branding', 'edit')->name('branding');
      Route::put('/branding', 'update')->name('branding.update');
      Route::delete('/branding/logo', 'deleteLogo')->name('branding.logo.delete');
      Route::post('/branding/logo/regenerate', 'regenerateConversions')->name('branding.logo.regenerate');
    });

    // Billing Settings
    Route::get('/billing', [BillingController::class, 'edit'])->name('billing');
    Route::post('/billing/subscribe/{plan:uuid}', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');

    // Integrations Settings
    Route::get('/integrations', [IntegrationsController::class, 'edit'])->name('integrations');
    Route::put('/integrations', [IntegrationsController::class, 'update']);
  });
