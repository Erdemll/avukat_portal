<?php

namespace App\Http\Requests;

use App\DeadlineStatus;
use App\Models\CaseFile;
use App\Models\Deadline;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDeadlineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Deadline::class) ?? false;
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
            'assigned_lawyer_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['nullable', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::enum(DeadlineStatus::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['case_file_id', 'assigned_lawyer_id'])) {
                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || ! $this->user()->can('manageLegalOperations', $caseFile)) {
                $validator->errors()->add('case_file_id', 'Bu hukuki dosyada işlem yapma yetkiniz yok.');

                return;
            }
            if ($this->filled('assigned_lawyer_id') && ! $caseFile->assignments()->where('lawyer_id', $this->integer('assigned_lawyer_id'))->whereNull('ended_at')->exists()) {
                $validator->errors()->add('assigned_lawyer_id', 'Süre sorumlusu dosyanın aktif avukatlarından biri olmalıdır.');
            }
        }];
    }
}
