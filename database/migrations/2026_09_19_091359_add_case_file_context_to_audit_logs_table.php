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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('case_file_id')->nullable()->after('event_id')->constrained()->restrictOnDelete();
            $table->index(['case_file_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['case_file_id']);
            $table->dropIndex(['case_file_id', 'created_at']);
            $table->dropColumn('case_file_id');
        });
    }
};
