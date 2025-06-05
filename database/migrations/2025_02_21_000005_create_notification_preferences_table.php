<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('notification_preferences', function (Blueprint $table) {
      $table->id();
      $table->string('preferenceable_type');
      $table->unsignedBigInteger('preferenceable_id');
      $table->string('type');
      $table->json('channels')->nullable();
      $table->boolean('enabled')->default(true);
      $table->timestamps();

      // Custom shorter index name
      $table->index(
        ['preferenceable_type', 'preferenceable_id'],
        'notif_pref_morph_idx'
      );

      $table->unique(
        ['preferenceable_type', 'preferenceable_id', 'type'],
        'notif_pref_unique_idx'
      );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('notification_preferences');
  }
};
