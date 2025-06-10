<?php

use App\Http\Controllers\SubscriberController;
use Illuminate\Support\Facades\Route;

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

    Route::get('/e/{subscriber:uuid}', 'edit')
      ->name('subscribers.edit');

    Route::delete('/{subscriber:uuid}', 'destroy')
      ->name('subscribers.destroy');

    // Bulk Actions
    Route::post('/bulk', 'bulkAction')
      ->name('subscribers.bulk');

    // Import/Export
    Route::get('/upload', 'upload')
      ->name('subscribers.upload');

    // Import/Export
    Route::post('/import', 'import')
      ->name('subscribers.import');

    Route::get('/export', 'export')
      ->name('subscribers.export');

    Route::post('/{subscriber:uuid}/lists', 'addToList')
      ->name('subscribers.lists.add');

    Route::delete('/{subscriber:uuid}/lists/{list:uuid}', 'removeFromList')
      ->name('subscribers.lists.remove');
  });
