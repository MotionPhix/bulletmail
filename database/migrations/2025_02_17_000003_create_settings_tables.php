<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('settings', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('type')->default('user')->index();
      $table->string('category')->index(); // 'preferences', 'notifications', etc.
      $table->json('settings');
      $table->json('metadata')->nullable();
      $table->timestamps();

      // Ensure unique settings per user and category
      $table->unique(['user_id', 'category']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('settings');
  }
};
