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
        Schema::create('mediations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->string('mediation_file_no')->nullable();
            $table->string('mediator_name')->nullable();
            $table->date('application_date')->nullable();
            $table->dateTime('meeting_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('status')->default('ongoing');
            $table->text('result')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['status', 'meeting_date']);
            $table->index(['case_file_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mediations');
    }
};
