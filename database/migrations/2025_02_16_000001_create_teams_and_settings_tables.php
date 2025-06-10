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
      $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
      $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
      $table->string('name');
      $table->boolean('personal_team');
      $table->boolean('is_default')->default(false);
      $table->timestamps();
      $table->softDeletes();

      // Add index for quick lookup of default teams
      $table->index(['organization_id', 'is_default']);
    });

    Schema::create('team_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('team_id')->constrained()->onDelete('cascade');
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('role');
      $table->timestamps();

      $table->unique(['team_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('team_user');
    Schema::dropIfExists('teams');
  }
};
