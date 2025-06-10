<?php

use App\Http\Controllers\SegmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('segments')->group(function () {
  Route::get('/', [SegmentController::class, 'index'])
    ->name('segments.index');

  Route::post('/', [SegmentController::class, 'store'])
    ->name('segments.store');

  Route::get('/s/{segment:uuid}', [SegmentController::class, 'show'])
    ->name('segments.show');

  Route::put('/{segment:uuid}', [SegmentController::class, 'update'])
    ->name('segments.update');

  Route::delete('/{segment:uuid}', [SegmentController::class, 'destroy'])
    ->name('segments.destroy');

  Route::post('/{segment:uuid}/duplicate', [SegmentController::class, 'duplicate'])
    ->name('segments.duplicate');

  Route::get('/{segment:uuid}/preview', [SegmentController::class, 'preview'])
    ->name('segments.preview');
});
