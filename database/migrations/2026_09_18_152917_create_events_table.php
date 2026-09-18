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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_no')->nullable()->unique();
            $table->foreignId('event_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_lawyer_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->text('description');
            $table->string('system_status')->default('open')->index();
            $table->text('current_process')->nullable();
            $table->string('priority')->default('normal');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
