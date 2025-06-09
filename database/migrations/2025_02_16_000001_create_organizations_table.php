<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('organizations', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();

      // Basic Info
      $table->string('name');
      $table->string('size');
      $table->string('industry');
      $table->string('website')->nullable();
      $table->string('phone')->nullable();

      // Branding
      $table->string('primary_color')->default('#4F46E5');
      $table->string('secondary_color')->default('#7C3AED');
      $table->text('email_header')->nullable();
      $table->text('email_footer')->nullable();

      // Email Settings
      $table->string('default_from_name')->nullable();
      $table->string('default_from_email')->nullable();
      $table->string('default_reply_to')->nullable();

      // Organization Settings
      $table->json('settings')->nullable();
      $table->json('preferences')->nullable();
      $table->json('integrations')->nullable();
      $table->json('metadata')->nullable();

      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('organizations');
  }
};
