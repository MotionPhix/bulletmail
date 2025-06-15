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
    Schema::create('campaign_lists', function (Blueprint $table) {
      $table->id();
      $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
      $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['campaign_id', 'mailing_list_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('campaign_lists');
  }
};
