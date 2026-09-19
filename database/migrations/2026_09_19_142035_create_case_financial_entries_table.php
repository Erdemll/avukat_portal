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
        Schema::create('case_financial_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_file_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('TRY');
            $table->text('description');
            $table->date('transaction_date');
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('case_financial_entries')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['case_file_id', 'currency', 'transaction_date'], 'cf_entries_case_currency_date_idx');
            $table->index(['type', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_financial_entries');
    }
};
