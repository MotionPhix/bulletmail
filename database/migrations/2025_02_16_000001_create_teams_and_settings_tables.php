<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('teams', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
      $table->string('name');
      $table->boolean('personal_team');
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('team_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('team_id')->constrained()->onDelete('cascade');
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('role');
      $table->timestamps();

      $table->unique(['team_id', 'user_id']);
    });

    Schema::create('team_settings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('team_id')->constrained()->onDelete('cascade');
      $table->json('email_settings')->nullable();
      $table->json('branding')->nullable();
      $table->json('quotas')->nullable();
      $table->json('notifications')->nullable();
      $table->json('marketing')->nullable();
      $table->json('company')->nullable();
      $table->json('sender')->nullable();
      $table->timestamps();

      $table->index('team_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('team_settings');
    Schema::dropIfExists('team_user');
    Schema::dropIfExists('teams');
  }
};
