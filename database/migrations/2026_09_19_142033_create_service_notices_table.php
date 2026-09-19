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
        Schema::create('service_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->string('sender')->nullable();
            $table->string('recipient')->nullable();
            $table->date('notification_date')->nullable();
            $table->date('service_date');
            $table->text('description')->nullable();
            $table->foreignId('document_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['service_date', 'type']);
            $table->index(['case_file_id', 'service_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_notices');
    }
};
