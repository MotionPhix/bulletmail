<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('template_versions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('template_id')->constrained('email_templates')->cascadeOnDelete();
      $table->string('version');
      $table->longText('content');
      $table->json('design')->nullable();
      $table->json('variables')->nullable();
      $table->string('sendgrid_version_id')->nullable();
      $table->boolean('is_active')->default(false);
      $table->timestamp('published_at')->nullable();
      $table->timestamps();

      $table->unique(['template_id', 'version']);
      $table->index(['template_id', 'is_active']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('template_versions');
  }
};
