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
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->foreignId('lawyer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->string('court')->nullable();
            $table->dateTime('hearing_at');
            $table->string('hearing_type')->nullable();
            $table->text('description')->nullable();
            $table->text('result')->nullable();
            $table->dateTime('next_hearing_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['hearing_at', 'status']);
            $table->index(['lawyer_id', 'hearing_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hearings');
    }
};
