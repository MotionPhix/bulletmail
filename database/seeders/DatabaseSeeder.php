<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      PlanSeeder::class,
      PermissionSeeder::class,
    ]);

    if (app()->environment('local', 'testing')) {
      $this->call([
        TestDataSeeder::class,
      ]);
    }
  }
}
