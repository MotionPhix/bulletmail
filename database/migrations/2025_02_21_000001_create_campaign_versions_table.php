<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('campaign_versions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
      $table->string('name')->nullable(); // For A/B testing
      $table->string('subject');
      $table->string('preview_text')->nullable();
      $table->longText('content');
      $table->json('template_data')->nullable();
      $table->decimal('send_percentage', 5, 2)->default(100); // For A/B testing
      $table->boolean('is_winner')->default(false);
      $table->timestamps();

      $table->index(['campaign_id', 'created_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('campaign_versions');
  }
};
