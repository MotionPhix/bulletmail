<?php

use App\Http\Controllers\Analytics\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'team.access'])->group(function () {
  Route::controller(AnalyticsController::class)
    ->prefix('analytics')
    ->group(function () {
      Route::get('/', 'index')->name('analytics.index');
      Route::get('/export', 'export')->name('analytics.export');
    });
});
