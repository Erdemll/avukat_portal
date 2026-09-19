<?php

namespace App\Http\Requests;

use App\CaseTypeCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('caseType')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('case_types', 'name')->ignore($this->route('caseType'))],
            'category' => ['required', Rule::enum(CaseTypeCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
