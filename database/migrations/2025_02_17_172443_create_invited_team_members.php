<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('invited_team_members', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->string('email');
      $table->string('role')->default('member');
      $table->string('invitation_token')->unique();
      $table->string('status')->default('pending');
      $table->integer('send_count')->default(0);
      $table->json('meta')->nullable();
      $table->timestamp('invited_at');
      $table->timestamp('accepted_at')->nullable();
      $table->timestamp('expires_at')->nullable();
      $table->timestamp('last_sent_at')->nullable();
      $table->timestamps();

      $table->unique(['team_id', 'email']);
      $table->index('invitation_token');
      $table->index('status');
      $table->index('expires_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invited_team_members');
  }
};
