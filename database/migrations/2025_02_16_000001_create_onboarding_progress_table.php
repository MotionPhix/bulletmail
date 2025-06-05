<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('onboarding_progress', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->json('completed_steps')->nullable();
      $table->json('skipped_steps')->nullable();
      $table->json('form_data')->nullable();
      $table->tinyInteger('current_step')->default(1);
      $table->boolean('is_completed')->default(false);
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->index('user_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('onboarding_progress');
  }
};
