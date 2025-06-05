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
    Schema::create('brandings', function (Blueprint $table) {
      $table->id();
      $table->string('company_name');
      $table->string('logo_path')->nullable();
      $table->string('primary_color')->default('#4f46e5');
      $table->string('accent_color')->default('#10b981');
      $table->text('email_header')->nullable();
      $table->text('email_footer')->nullable();
      $table->text('custom_css')->nullable();
      $table->timestamps();

      $table->foreignId('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('brandings');
  }
};
