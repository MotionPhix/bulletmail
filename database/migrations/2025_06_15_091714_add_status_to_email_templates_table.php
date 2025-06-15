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
    Schema::table('email_templates', function (Blueprint $table) {
      // Add status column to track template status
      $table->enum('status', ['draft', 'published', 'archived', 'deleted'])
        ->default('draft')
        ->after('last_synced_at')
        ->comment('Status of the email template: draft, published, archived, or deleted');

      // Add index for faster querying by status
      $table->index('status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('email_templates', function (Blueprint $table) {
      // Drop the status column
      $table->dropColumn('status');

      // Drop the index on status
      $table->dropIndex(['status']);
    });
  }
};
