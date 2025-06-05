<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('segments', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->string('description')->nullable();
      $table->json('conditions')->nullable();
      $table->json('rules')->nullable();
      $table->integer('subscriber_count')->default(0);
      $table->timestamp('last_calculated_at')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['team_id', 'name']);
    });

    Schema::create('campaign_events', function (Blueprint $table) {
      $table->id();
      $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
      $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
      $table->string('type');
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->index(['campaign_id', 'type']);
      $table->index(['subscriber_id', 'type']);
    });

    Schema::create('tracking_events', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('event_type');
      $table->string('email')->nullable();
      $table->string('url')->nullable();
      $table->string('ip_address')->nullable();
      $table->string('user_agent')->nullable();
      $table->json('metadata')->nullable();
      $table->morphs('trackable');
      $table->timestamps();

      $table->index(['event_type', 'created_at']);
      $table->index(['email', 'event_type']);
    });

    Schema::create('analytics', function (Blueprint $table) {
      $table->id();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->string('metric');
      $table->integer('value')->default(0);
      $table->json('dimensions')->nullable();
      $table->date('date');
      $table->timestamps();

      $table->index(['team_id', 'metric', 'date']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('analytics');
    Schema::dropIfExists('tracking_events');
    Schema::dropIfExists('campaign_events');
    Schema::dropIfExists('segments');
  }
};
