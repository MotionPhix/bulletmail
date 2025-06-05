<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('subscribers', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      // Basic Information
      $table->string('email');
      $table->string('first_name')->nullable();
      $table->string('last_name')->nullable();
      $table->json('custom_fields')->nullable();

      // Status and Metadata
      $table->string('status')->default('subscribed');
      $table->timestamp('subscribed_at')->nullable();
      $table->timestamp('unsubscribed_at')->nullable();
      $table->string('unsubscribe_reason')->nullable();
      $table->string('source')->nullable();
      $table->string('ip_address')->nullable();
      $table->json('metadata')->nullable();

      // Email Engagement
      $table->timestamp('last_emailed_at')->nullable();
      $table->timestamp('last_opened_at')->nullable();
      $table->timestamp('last_clicked_at')->nullable();
      $table->integer('emails_received')->default(0);
      $table->integer('emails_opened')->default(0);
      $table->integer('emails_clicked')->default(0);

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->unique(['email', 'team_id']);
      $table->index(['team_id', 'status']);
    });

    Schema::create('mailing_lists', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('name');
      $table->string('description')->nullable();
      $table->string('type')->default('standard');
      $table->json('settings')->nullable();
      $table->json('segment_rules')->nullable();
      $table->boolean('double_opt_in')->default(false);
      $table->string('welcome_email_id')->nullable();
      $table->unsignedInteger('subscriber_count')->default(0);
      $table->timestamp('last_synced_at')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->unique(['team_id', 'name']);
      $table->index(['team_id', 'type']);
    });

    Schema::create('mailing_list_subscriber', function (Blueprint $table) {
      $table->id();
      $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
      $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
      $table->string('status')->default('subscribed');
      $table->timestamp('subscribed_at')->nullable();
      $table->timestamp('unsubscribed_at')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();

      $table->unique(['mailing_list_id', 'subscriber_id']);
      $table->index(['mailing_list_id', 'status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mailing_list_subscriber');
    Schema::dropIfExists('mailing_lists');
    Schema::dropIfExists('subscribers');
  }
};
