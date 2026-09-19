<?php

namespace App\Http\Requests;

use App\CaseAssignmentRequestType;
use App\Models\CaseAssignmentRequest;
use App\Models\CaseFile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCaseAssignmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('type') === CaseAssignmentRequestType::Claim->value && $this->filled('case_no')) {
            $caseFileId = CaseFile::query()->where('case_no', $this->string('case_no')->trim()->toString())->value('id');
            $this->merge(['case_file_id' => $caseFileId]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', CaseAssignmentRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_file_id' => ['nullable', 'integer', Rule::exists(CaseFile::class, 'id'), Rule::requiredIf($this->input('type') === CaseAssignmentRequestType::Transfer->value)],
            'case_no' => ['nullable', 'string', 'max:30', Rule::requiredIf($this->input('type') === CaseAssignmentRequestType::Claim->value)],
            'type' => ['required', Rule::enum(CaseAssignmentRequestType::class)],
            'requested_to' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'))],
            'reason' => ['required', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['case_file_id', 'case_no', 'type', 'requested_to'])) {
                return;
            }
            if ($this->input('type') === CaseAssignmentRequestType::Claim->value && ! $this->filled('case_file_id')) {
                $validator->errors()->add('case_no', 'Dosya numarası bulunamadı veya talep oluşturulamaz.');

                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || $caseFile->status->value === 'closed') {
                $validator->errors()->add('case_file_id', 'Kapalı veya geçersiz dosya için talep oluşturulamaz.');

                return;
            }
            $isAssigned = $caseFile->assignments()->where('lawyer_id', $this->user()->id)->whereNull('ended_at')->exists();
            if ($this->input('type') === 'transfer') {
                if (! $isAssigned) {
                    $validator->errors()->add('case_file_id', 'Yalnız aktif olarak atandığınız dosyayı devredebilirsiniz.');
                }
                if (! $this->filled('requested_to')) {
                    $validator->errors()->add('requested_to', 'Devir talebinde hedef avukat zorunludur.');
                }
                if ($this->integer('requested_to') === $this->user()->id) {
                    $validator->errors()->add('requested_to', 'Dosyayı kendinize devredemezsiniz.');
                }
            }
            if ($this->input('type') === 'claim') {
                if ($isAssigned) {
                    $validator->errors()->add('case_file_id', 'Zaten atandığınız dosya için atama talebi oluşturamazsınız.');
                }
                if ($this->filled('requested_to')) {
                    $validator->errors()->add('requested_to', 'Atama talebinde hedef avukat seçilmez.');
                }
            }
        }];
    }
}
