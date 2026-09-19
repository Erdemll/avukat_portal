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
        Schema::create('case_file_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->foreignId('lawyer_id')->constrained('users')->restrictOnDelete();
            $table->string('role')->default('lawyer');
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('active_marker')->nullable()->default(true);
            $table->boolean('active_lead_marker')->nullable();
            $table->foreignId('ended_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['case_file_id', 'ended_at']);
            $table->index(['lawyer_id', 'ended_at']);
            $table->unique(['case_file_id', 'lawyer_id', 'active_marker']);
            $table->unique(['case_file_id', 'active_lead_marker']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_file_assignments');
    }
};
