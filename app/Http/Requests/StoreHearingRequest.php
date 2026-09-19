<?php

namespace App\Http\Requests;

use App\HearingStatus;
use App\Models\CaseFile;
use App\Models\Hearing;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHearingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Hearing::class) ?? false;
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
            'lawyer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'))],
            'title' => ['required', 'string', 'max:255'],
            'court' => ['nullable', 'string', 'max:255'],
            'hearing_at' => ['required', 'date'],
            'hearing_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'result' => ['nullable', 'string', 'max:5000'],
            'next_hearing_at' => ['nullable', 'date', 'after:hearing_at'],
            'status' => ['required', Rule::enum(HearingStatus::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['case_file_id', 'lawyer_id'])) {
                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || ! $this->user()->can('manageLegalOperations', $caseFile)) {
                $validator->errors()->add('case_file_id', 'Bu hukuki dosyada işlem yapma yetkiniz yok.');

                return;
            }
            if ($this->filled('lawyer_id') && ! $caseFile->assignments()->where('lawyer_id', $this->integer('lawyer_id'))->whereNull('ended_at')->exists()) {
                $validator->errors()->add('lawyer_id', 'Duruşma avukatı dosyanın aktif avukatlarından biri olmalıdır.');
            }
        }];
    }
}
