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
        Schema::create('case_file_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('relation_type')->default('related');
            $table->foreignId('linked_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();
            $table->unique(['case_file_id', 'event_id']);
            $table->index(['event_id', 'relation_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_file_event');
    }
};
