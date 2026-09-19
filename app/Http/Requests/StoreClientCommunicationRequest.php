<?php

namespace App\Http\Requests;

use App\CommunicationType;
use App\Models\CaseFile;
use App\Models\Client;
use App\Models\ClientCommunication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClientCommunicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ClientCommunication::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists(Client::class, 'id')],
            'case_file_id' => ['nullable', 'integer', Rule::exists(CaseFile::class, 'id')],
            'type' => ['required', Rule::enum(CommunicationType::class)],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'communication_at' => ['required', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['client_id', 'case_file_id'])) {
                return;
            }
            $client = Client::query()->find($this->integer('client_id'));
            if ($client === null || ! $this->user()->can('view', $client)) {
                $validator->errors()->add('client_id', 'Bu müvekkile erişiminiz yok.');

                return;
            }
            if ($this->filled('case_file_id')) {
                $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
                if ($caseFile === null || ! $this->user()->can('view', $caseFile) || ! $caseFile->activeParties()->whereKey($client->party_id)->exists()) {
                    $validator->errors()->add('case_file_id', 'Seçilen dosya bu müvekkile ait değil veya erişiminiz yok.');
                }
            }
        }];
    }
}
