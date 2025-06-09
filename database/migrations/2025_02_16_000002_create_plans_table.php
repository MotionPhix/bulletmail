<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('plans', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->string('name');
      $table->string('slug')->unique();
      $table->string('description')->nullable();
      $table->integer('price');
      $table->string('currency', 3)->default('MWK');
      $table->integer('trial_days')->default(0);
      $table->boolean('is_active')->default(true);
      $table->boolean('is_featured')->default(false);
      $table->integer('sort_order')->default(0);
      $table->json('features');
      $table->json('metadata')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index('slug');
      $table->index(['is_active', 'sort_order']);
    });

    Schema::create('subscriptions', function (Blueprint $table) {
      $table->id();
      $table->uuid('uuid')->unique();
      $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
      $table->string('status');
      $table->timestamp('starts_at');
      $table->timestamp('ends_at')->nullable();
      $table->timestamp('trial_ends_at')->nullable();
      $table->timestamp('cancelled_at')->nullable();
      $table->timestamp('last_payment_at')->nullable();
      $table->string('payment_method')->nullable();
      $table->string('payment_reference')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->index(['organization_id', 'status']);
      $table->index('status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('subscriptions');
    Schema::dropIfExists('plans');
  }
};
