<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCaseFileAssistantsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('assignAssistants', $this->route('caseFile')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assistant_ids' => ['nullable', 'array'],
            'assistant_ids.*' => ['required', 'integer', 'distinct', Rule::exists(User::class, 'id')->where(fn ($query) => $query
                ->where('is_active', true)
                ->whereIn('role_id', Role::query()->where('slug', 'assistant')->select('id')))],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }
}
