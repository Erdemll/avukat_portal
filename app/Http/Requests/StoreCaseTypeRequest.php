<?php

namespace App\Http\Requests;

use App\CaseTypeCategory;
use App\Models\CaseType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCaseTypeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug($this->string('name'))]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CaseType::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('case_types', 'name')],
            'slug' => ['required', 'string', 'max:255', Rule::unique('case_types', 'slug')],
            'category' => ['required', Rule::enum(CaseTypeCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
