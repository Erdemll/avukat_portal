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
        Schema::create('case_assignment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('requested_to')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->string('status')->default('pending');
            $table->boolean('active_marker')->nullable()->default(true);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->unique(['case_file_id', 'requested_by', 'type', 'active_marker'], 'cf_assignment_requests_pending_unique');
            $table->index(['status', 'created_at']);
            $table->index(['requested_to', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_assignment_requests');
    }
};
