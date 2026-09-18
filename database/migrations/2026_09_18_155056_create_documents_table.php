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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_update_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size');
            $table->string('document_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
