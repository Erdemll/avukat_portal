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
        Schema::table('case_files', function (Blueprint $table) {
            $table->string('import_source')->nullable()->after('case_no');
            $table->string('external_reference')->nullable()->after('import_source');
            $table->unique(['import_source', 'external_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('case_files', function (Blueprint $table) {
            $table->dropUnique(['import_source', 'external_reference']);
            $table->dropColumn(['import_source', 'external_reference']);
        });
    }
};
