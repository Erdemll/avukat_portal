<?php

namespace App\Http\Requests;

use App\CaseFileStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseFileStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(CaseFileStatus::class)],
            'reason' => ['nullable', 'string', 'max:2000', Rule::requiredIf(
                $this->input('status') !== $this->route('caseFile')->status->value
                && in_array($this->input('status'), ['resolved', 'closed'], true)
            )],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
