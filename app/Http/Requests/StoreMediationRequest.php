<?php

namespace App\Http\Requests;

use App\MediationStatus;
use App\Models\CaseFile;
use App\Models\Mediation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMediationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Mediation::class) ?? false;
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
            'mediation_file_no' => ['nullable', 'string', 'max:100'],
            'mediator_name' => ['nullable', 'string', 'max:255'],
            'application_date' => ['nullable', 'date'],
            'meeting_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date', 'after_or_equal:application_date', Rule::requiredIf(in_array($this->input('status'), ['agreement', 'no_agreement'], true))],
            'status' => ['required', Rule::enum(MediationStatus::class)],
            'result' => ['nullable', 'string', 'max:5000', Rule::requiredIf(in_array($this->input('status'), ['agreement', 'no_agreement'], true))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('case_file_id')) {
                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || ! $this->user()->can('manageLegalOperations', $caseFile)) {
                $validator->errors()->add('case_file_id', 'Bu dosyada arabuluculuk işlemi yapma yetkiniz yok.');
            }
        }];
    }
}
