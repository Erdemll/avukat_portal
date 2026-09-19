<?php

namespace App\Http\Requests;

use App\CaseProceedingType;
use App\EventPriority;
use App\Models\CaseFile;
use App\Models\CaseType;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class StoreCaseFileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->user()?->isManager() && $this->filled('lead_lawyer_id')) {
            $this->merge(['lawyer_ids' => collect($this->input('lawyer_ids', []))->push($this->input('lead_lawyer_id'))->unique()->values()->all()]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CaseFile::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_type_id' => ['required', Rule::exists(CaseType::class, 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', Rule::enum(EventPriority::class)],
            'description' => ['nullable', 'string', 'max:10000'],
            'opened_at' => ['required', 'date'],
            'lawyer_ids' => [Rule::requiredIf($this->user()?->isManager()), Rule::prohibitedIf($this->user()?->isLawyer()), 'array', 'min:1'],
            'lawyer_ids.*' => ['integer', 'distinct', $this->activeLawyerRule()],
            'lead_lawyer_id' => [Rule::requiredIf($this->user()?->isManager()), Rule::prohibitedIf($this->user()?->isLawyer()), 'integer', $this->activeLawyerRule()],
            'client_party_ids' => ['nullable', 'array'],
            'client_party_ids.*' => ['integer', 'distinct', Rule::exists(Client::class, 'party_id')],
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $lawyerIds = collect($this->input('lawyer_ids', []))->map(fn ($id): int => (int) $id);
            if ($this->user()?->isManager() && ! $lawyerIds->contains($this->integer('lead_lawyer_id'))) {
                $validator->errors()->add('lead_lawyer_id', 'Lider avukat, atanan avukatlar arasında bulunmalıdır.');
            }

            if ($validator->errors()->has('client_party_ids')) {
                return;
            }

            $requestedClientIds = collect($this->input('client_party_ids', []))->map(fn ($id) => (int) $id)->unique();
            $visibleClientCount = Client::query()->visibleTo($this->user())->whereIn('party_id', $requestedClientIds)->count();
            if ($visibleClientCount !== $requestedClientIds->count()) {
                $validator->errors()->add('client_party_ids', 'Seçilen müvekkillerden en az birine erişiminiz yok.');
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
