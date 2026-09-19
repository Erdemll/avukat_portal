<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReviewCaseAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('review', $this->route('caseAssignmentRequest')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'requested_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'))],
            'make_lead' => ['nullable', 'boolean'],
            'decision_note' => ['nullable', 'string', 'max:5000', Rule::requiredIf($this->input('decision') === 'rejected')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $assignmentRequest = $this->route('caseAssignmentRequest');
            if ($this->filled('requested_to') && ($this->input('decision') !== 'approved' || $assignmentRequest->type->value !== 'transfer')) {
                $validator->errors()->add('requested_to', 'Hedef avukat yalnız onaylanan devir taleplerinde değiştirilebilir.');
            }
        }];
    }
}
