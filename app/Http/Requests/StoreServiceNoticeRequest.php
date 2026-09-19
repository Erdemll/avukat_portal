<?php

namespace App\Http\Requests;

use App\Models\CaseFile;
use App\Models\Document;
use App\Models\ServiceNotice;
use App\ServiceNoticeType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreServiceNoticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', ServiceNotice::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_file_id' => ['required', 'integer', Rule::exists(CaseFile::class, 'id')],
            'type' => ['required', Rule::enum(ServiceNoticeType::class)],
            'sender' => ['nullable', 'string', 'max:255'],
            'recipient' => ['nullable', 'string', 'max:255'],
            'notification_date' => ['nullable', 'date'],
            'service_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document_id' => ['nullable', 'integer', Rule::exists(Document::class, 'id')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['case_file_id', 'document_id'])) {
                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || ! $this->user()->can('manageLegalOperations', $caseFile)) {
                $validator->errors()->add('case_file_id', 'Bu dosyada tebligat işlemi yapma yetkiniz yok.');

                return;
            }
            if ($this->filled('document_id') && ! $caseFile->documents()->whereKey($this->integer('document_id'))->exists()) {
                $validator->errors()->add('document_id', 'Seçilen evrak bu hukuki dosyaya ait değil.');
            }
        }];
    }
}
