<?php

namespace App\Http\Requests;

use App\EventPriority;
use App\LegalTaskStatus;
use App\Models\CaseFile;
use App\Models\LegalTask;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLegalTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', LegalTask::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'case_file_id' => ['nullable', 'integer', Rule::exists(CaseFile::class, 'id')],
            'assigned_to' => ['required', 'integer', Rule::exists(User::class, 'id')->where('is_active', true)->whereIn('role_id', Role::query()->whereIn('slug', ['lawyer', 'manager'])->select('id'))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::enum(EventPriority::class)],
            'due_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(LegalTaskStatus::class)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['case_file_id', 'assigned_to'])) {
                return;
            }
            if (! $this->filled('case_file_id')) {
                if (! $this->user()->isManager() && $this->integer('assigned_to') !== $this->user()->id) {
                    $validator->errors()->add('assigned_to', 'Dosyasız kişisel görev yalnız kendinize atanabilir.');
                }

                return;
            }
            $caseFile = CaseFile::query()->find($this->integer('case_file_id'));
            if ($caseFile === null || ! $this->user()->can('manageLegalOperations', $caseFile)) {
                $validator->errors()->add('case_file_id', 'Bu hukuki dosyada işlem yapma yetkiniz yok.');

                return;
            }
            if (! $this->user()->isManager() && $this->integer('assigned_to') !== $this->user()->id && ! $caseFile->assignments()->where('lawyer_id', $this->integer('assigned_to'))->whereNull('ended_at')->exists()) {
                $validator->errors()->add('assigned_to', 'Görev yalnız size veya dosyanın aktif avukatlarından birine atanabilir.');
            }
        }];
    }
}
