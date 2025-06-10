<?php

use App\Http\Controllers\MailingListController;
use Illuminate\Support\Facades\Route;

Route::prefix('lists')->group(function () {
  Route::get('/', [MailingListController::class, 'index'])
    ->name('lists.index');

  Route::post('/', [MailingListController::class, 'store'])
    ->name('lists.store');

  Route::get('/s/{list:uuid}', [MailingListController::class, 'show'])
    ->name('lists.show');

  Route::put('/{list:uuid}', [MailingListController::class, 'update'])
    ->name('lists.update');

  Route::delete('/{list:uuid}', [MailingListController::class, 'destroy'])
    ->name('lists.destroy');

  // List Management
  Route::post('/{list:uuid}/duplicate', [MailingListController::class, 'duplicate'])
    ->name('lists.duplicate');

  Route::post('/{list:uuid}/sync', [MailingListController::class, 'synchronize'])
    ->name('lists.sync');

  Route::get('/{list:uuid}/export', [MailingListController::class, 'export'])
    ->name('lists.export');

  // Subscriber Management within Lists
  Route::post('/{list:uuid}/subscribers', [MailingListController::class, 'addSubscribers'])
    ->name('lists.subscribers.add');

  Route::delete('/{list:uuid}/subscribers', [MailingListController::class, 'removeSubscribers'])
    ->name('lists.subscribers.remove');
});
