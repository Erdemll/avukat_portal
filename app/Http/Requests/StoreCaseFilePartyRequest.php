<?php

namespace App\Http\Requests;

use App\CaseFilePartyRole;
use App\CasePartySide;
use App\Models\Party;
use App\PartyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCaseFilePartyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageParties', $this->route('caseFile')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'party_id' => ['nullable', 'integer', Rule::exists(Party::class, 'id')],
            'type' => ['nullable', Rule::enum(PartyType::class), 'required_without:party_id'],
            'name' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $this->filled('party_id') && $this->input('type') === PartyType::Individual->value)],
            'surname' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $this->filled('party_id') && $this->input('type') === PartyType::Individual->value)],
            'company_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(! $this->filled('party_id') && $this->input('type') === PartyType::Company->value)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'role' => ['required', Rule::enum(CaseFilePartyRole::class)],
            'side' => ['required', Rule::enum(CasePartySide::class)],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('party_id') || ! $this->filled('party_id')) {
                return;
            }

            if (! Party::query()->visibleTo($this->user())->whereKey($this->integer('party_id'))->exists()) {
                $validator->errors()->add('party_id', 'Seçilen tarafa erişiminiz yok.');
            }
        }];
    }
}
