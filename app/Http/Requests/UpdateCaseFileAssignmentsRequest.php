<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class UpdateCaseFileAssignmentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('lead_lawyer_id')) {
            $this->merge(['lawyer_ids' => collect($this->input('lawyer_ids', []))->push($this->input('lead_lawyer_id'))->unique()->values()->all()]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('assign', $this->route('caseFile')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lawyer_ids' => ['required', 'array', 'min:1'],
            'lawyer_ids.*' => ['required', 'integer', 'distinct', $this->activeLawyerRule()],
            'lead_lawyer_id' => ['required', 'integer', $this->activeLawyerRule()],
            'reason' => ['nullable', 'string', 'max:2000'],
            'lock_version' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $lawyerIds = collect($this->input('lawyer_ids', []))->map(fn ($id): int => (int) $id);
            if (! $lawyerIds->contains($this->integer('lead_lawyer_id'))) {
                $validator->errors()->add('lead_lawyer_id', 'Lider avukat, atanan avukatlar arasında bulunmalıdır.');
            }
        }];
    }

    private function activeLawyerRule(): Exists
    {
        return Rule::exists(User::class, 'id')->where(function ($query): void {
            $query->where('is_active', true)
                ->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'));
        });
    }
}
