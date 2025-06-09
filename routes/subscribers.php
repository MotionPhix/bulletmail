<?php

use App\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'team.access'])->group(function () {
  // Subscriber Management
  Route::prefix('subscribers')
    ->controller(SubscriberController::class)
    ->group(function () {
    Route::get('/', 'index')
      ->name('subscribers.index');

    Route::get('/add-subscriber', 'create')
      ->name('subscribers.create');

    Route::post('/', 'store')
      ->name('subscribers.store');

    Route::get('/s/{subscriber:uuid}', 'show')
      ->name('subscribers.show');

    Route::put('/{subscriber:uuid}', 'update')
      ->name('subscribers.update');

    Route::delete('/{subscriber:uuid}', 'destroy')
      ->name('subscribers.destroy');

    // Bulk Actions
    Route::post('/bulk', 'bulkAction')
      ->name('subscribers.bulk');

    // Import/Export
    Route::post('/import', 'import')
      ->name('subscribers.import');

    Route::get('/export', 'export')
      ->name('subscribers.export');
  });

  // Public Subscriber Actions (no auth required)
  Route::prefix('s')
    ->controller(SubscriberController::class)
    ->group(function () {
    Route::get('/unsubscribe/{uuid}', 'unsubscribe')
      ->name('subscribers.unsubscribe')
      ->withoutMiddleware(['auth', 'verified', 'team.access']);

    Route::get('/preferences/{uuid}', 'preferences')
      ->name('subscribers.preferences')
      ->withoutMiddleware(['auth', 'verified', 'team.access']);

    Route::put('/preferences/{uuid}', 'updatePreferences')
      ->name('subscribers.preferences.update')
      ->withoutMiddleware(['auth', 'verified', 'team.access']);
  });
});
