<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('email_templates', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      // Template Details
      $table->string('name');
      $table->string('description')->nullable();
      $table->string('subject');
      $table->text('content');
      $table->string('preview_text')->nullable();
      $table->string('category')->default('newsletter');
      $table->string('type')->default('drag-drop');

      // Template Design & Variables
      $table->json('design')->nullable();
      $table->json('variables')->nullable();
      $table->json('tags')->nullable();

      // SendGrid Integration
      $table->string('sendgrid_template_id')->nullable();
      $table->timestamp('last_synced_at')->nullable();

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index(['team_id', 'category']);
      $table->index('sendgrid_template_id');
    });

    Schema::create('campaigns', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('team_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();

      // Campaign Details
      $table->string('name');
      $table->string('description')->nullable();
      $table->string('subject');
      $table->text('content')->nullable();
      $table->string('preview_text')->nullable();
      $table->string('from_name')->nullable();
      $table->string('from_email')->nullable();
      $table->string('reply_to')->nullable();

      // Scheduling
      $table->string('status')->default('draft');
      $table->timestamp('scheduled_at')->nullable();
      $table->timestamp('started_at')->nullable();
      $table->timestamp('completed_at')->nullable();

      // Recipients
      $table->json('recipient_lists')->nullable();
      $table->json('recipient_segments')->nullable();
      $table->integer('total_recipients')->default(0);

      // SendGrid Integration
      $table->string('sendgrid_campaign_id')->nullable();
      $table->json('sendgrid_settings')->nullable();

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index(['team_id', 'status']);
      $table->index(['status', 'scheduled_at']);
    });

    Schema::create('campaign_stats', function (Blueprint $table) {
      $table->id();
      $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
      $table->integer('recipients_count')->default(0);
      $table->integer('delivered_count')->default(0);
      $table->integer('opened_count')->default(0);
      $table->integer('clicked_count')->default(0);
      $table->integer('bounced_count')->default(0);
      $table->integer('complained_count')->default(0);
      $table->integer('unsubscribed_count')->default(0);
      $table->timestamps();

      $table->index(['campaign_id', 'created_at']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('campaign_stats');
    Schema::dropIfExists('campaigns');
    Schema::dropIfExists('email_templates');
  }
};
