<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Models\PartyIdentifier;
use App\PartyIdentifierType;
use App\PartyType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Client::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PartyType::class)],
            'name' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->input('type') === PartyType::Individual->value)],
            'surname' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->input('type') === PartyType::Individual->value)],
            'company_name' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->input('type') === PartyType::Company->value)],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'party_notes' => ['nullable', 'string', 'max:5000'],
            'client_since' => ['nullable', 'date'],
            'client_notes' => ['nullable', 'string', 'max:5000'],
            'identifier_type' => [Rule::prohibitedIf(! $this->user()?->isManager()), 'nullable', Rule::enum(PartyIdentifierType::class), 'required_with:identifier_value'],
            'identifier_value' => [Rule::prohibitedIf(! $this->user()?->isManager()), 'nullable', 'string', 'max:50', 'required_with:identifier_type'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->filled(['identifier_type', 'identifier_value'])) {
                return;
            }

            if (PartyIdentifier::query()
                ->where('type', $this->string('identifier_type')->toString())
                ->where('value_hash', PartyIdentifier::hashValue($this->string('identifier_value')->toString()))
                ->where('country_code', 'TR')
                ->exists()) {
                $validator->errors()->add('identifier_value', 'Bu kimlik veya vergi numarası daha önce kaydedilmiş.');
            }
        }];
    }
}
