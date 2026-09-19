<?php

namespace App\Http\Requests;

use App\FinancialEntryType;
use App\Models\CaseFile;
use App\Models\CaseFinancialEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaseFinancialEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CaseFinancialEntry::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_file_id' => ['required', 'integer', Rule::exists(CaseFile::class, 'id')],
            'type' => ['required', Rule::enum(FinancialEntryType::class), Rule::notIn(['reversal'])],
            'amount' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999999.99'],
            'currency' => ['required', Rule::in(config('legal.currencies'))],
            'description' => ['required', 'string', 'max:5000'],
            'transaction_date' => ['required', 'date'],
        ];
    }
}
