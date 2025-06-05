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
      $table->morphs('settingable');
      $table->string('key');
      $table->json('value')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->unique(['settingable_type', 'settingable_id', 'key']);
    });

    Schema::create('registration_data', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('step');
      $table->json('data')->nullable();
      $table->boolean('completed')->default(false);
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();

      $table->index(['user_id', 'step']);
    });

    Schema::create('email_quotas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->integer('monthly_limit')->default(1000);
      $table->integer('monthly_used')->default(0);
      $table->integer('daily_limit')->default(100);
      $table->integer('daily_used')->default(0);
      $table->timestamp('last_reset_at')->nullable();
      $table->timestamps();

      $table->index('user_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('email_quotas');
    Schema::dropIfExists('registration_data');
    Schema::dropIfExists('settings');
  }
};
