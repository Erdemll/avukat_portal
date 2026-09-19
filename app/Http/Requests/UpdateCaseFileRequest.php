<?php

namespace App\Http\Requests;

use App\CaseProceedingType;
use App\EventPriority;
use App\Models\CaseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('caseFile')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_type_id' => ['required', Rule::exists(CaseType::class, 'id')->where(fn ($query) => $query
                ->where('is_active', true)
                ->orWhere('id', $this->route('caseFile')->case_type_id))],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::enum(EventPriority::class)],
            'description' => ['nullable', 'string', 'max:10000'],
            'opened_at' => ['required', 'date'],
            'lock_version' => ['required', 'integer', 'min:0'],
            'proceeding_type' => ['nullable', Rule::enum(CaseProceedingType::class), 'required_with:courthouse,authority_name,court_type,principal_year,principal_number,decision_year,decision_number,external_file_number'],
            'courthouse' => ['nullable', 'string', 'max:255'],
            'authority_name' => ['nullable', 'string', 'max:255'],
            'court_type' => ['nullable', 'string', 'max:255'],
            'principal_year' => ['nullable', 'integer', 'digits:4', 'min:2000', 'max:9999'],
            'principal_number' => ['nullable', 'string', 'max:50'],
            'decision_year' => ['nullable', 'integer', 'digits:4', 'min:2000', 'max:9999'],
            'decision_number' => ['nullable', 'string', 'max:50'],
            'external_file_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
