<?php

namespace App\Providers;

use App\Services\SendGrid\SendGridService;
use Illuminate\Support\ServiceProvider;

class SendGridServiceProvider extends ServiceProvider
{
  /**
   * Register services.
   */
  public function register(): void
  {
    $this->app->singleton(SendGridService::class, function ($app) {
      return new SendGridService(
        config('services.sendgrid.key'),
        config('services.sendgrid.from_email'),
        config('services.sendgrid.from_name')
      );
    });
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    //
  }
}
