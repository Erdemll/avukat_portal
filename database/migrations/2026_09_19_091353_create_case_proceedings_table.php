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
        Schema::create('case_proceedings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->string('type')->index();
            $table->string('courthouse')->nullable();
            $table->string('authority_name')->nullable();
            $table->string('court_type')->nullable();
            $table->unsignedSmallInteger('principal_year')->nullable();
            $table->string('principal_number')->nullable();
            $table->unsignedSmallInteger('decision_year')->nullable();
            $table->string('decision_number')->nullable();
            $table->string('external_file_number')->nullable();
            $table->string('status')->default('active')->index();
            $table->date('opened_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->timestamps();
            $table->index(['case_file_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_proceedings');
    }
};
