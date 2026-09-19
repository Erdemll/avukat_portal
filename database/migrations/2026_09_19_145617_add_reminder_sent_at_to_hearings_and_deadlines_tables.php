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
        Schema::table('hearings', function (Blueprint $table) {
            $table->dateTime('reminder_sent_at')->nullable()->after('status')->index();
        });

        Schema::table('deadlines', function (Blueprint $table) {
            $table->dateTime('reminder_sent_at')->nullable()->after('completed_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hearings', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('deadlines', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
