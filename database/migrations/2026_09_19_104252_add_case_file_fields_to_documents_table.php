<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->change();
            $table->foreignId('case_file_id')->nullable()->after('event_update_id')->constrained()->restrictOnDelete();
            $table->foreignId('folder_id')->nullable()->after('case_file_id')->constrained('document_folders')->restrictOnDelete();
            $table->string('title')->nullable()->after('folder_id');
            $table->timestamp('archived_at')->nullable()->after('document_type');
            $table->index(['case_file_id', 'document_type']);
            $table->index(['folder_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['folder_id', 'created_at']);
            $table->dropIndex(['case_file_id', 'document_type']);
            $table->dropConstrainedForeignId('folder_id');
            $table->dropConstrainedForeignId('case_file_id');
            $table->dropColumn(['title', 'archived_at']);
            $table->foreignId('event_id')->nullable(false)->change();
        });
    }
};
