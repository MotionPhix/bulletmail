<?php

use App\Http\Controllers\Subscriber\{
  SubscriberController,
  SegmentController,
  ImportController
};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'team.access'])->group(function () {
  // Subscribers
  Route::controller(SubscriberController::class)
    ->prefix('subscribers')
    ->group(function () {
      Route::get('/', 'index')->name('subscribers.index');
      Route::post('/', 'store')->name('subscribers.store');
      Route::get('/{subscriber:uuid}', 'show')->name('subscribers.show');
      Route::put('/{subscriber:uuid}', 'update')->name('subscribers.update');
      Route::delete('/{subscriber:uuid}', 'destroy')->name('subscribers.destroy');
    });

  // Segments
  Route::controller(SegmentController::class)
    ->prefix('segments')
    ->group(function () {
      Route::get('/', 'index')->name('segments.index');
      Route::post('/', 'store')->name('segments.store');
      Route::get('/{segment:uuid}', 'show')->name('segments.show');
      Route::put('/{segment:uuid}', 'update')->name('segments.update');
      Route::delete('/{segment:uuid}', 'destroy')->name('segments.destroy');
    });

  // Import
  Route::controller(ImportController::class)
    ->prefix('subscribers/import')
    ->group(function () {
      Route::get('/', 'show')->name('subscribers.import');
      Route::post('/', 'store')->name('subscribers.import.store');
    });
});
