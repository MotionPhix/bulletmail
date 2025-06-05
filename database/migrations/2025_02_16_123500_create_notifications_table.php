<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('notifications', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignId('team_id')->nullable()->constrained()->cascadeOnDelete();
      $table->string('type');
      $table->morphs('notifiable');  // This already creates the index
      $table->text('data');
      $table->string('message')->nullable();
      $table->timestamp('read_at')->nullable();
      $table->timestamps();

      // Remove the duplicate index and keep only these
      $table->index(['team_id', 'created_at']);
      $table->index('read_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('notifications');
  }
};
