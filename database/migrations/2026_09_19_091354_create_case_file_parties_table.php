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
        Schema::create('case_file_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->foreignId('party_id')->constrained()->restrictOnDelete();
            $table->string('role')->index();
            $table->string('side')->index();
            $table->boolean('is_primary')->default(false);
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->boolean('active_marker')->nullable()->default(true);
            $table->timestamps();
            $table->unique(['case_file_id', 'party_id', 'role', 'active_marker']);
            $table->index(['case_file_id', 'side', 'left_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_file_parties');
    }
};
