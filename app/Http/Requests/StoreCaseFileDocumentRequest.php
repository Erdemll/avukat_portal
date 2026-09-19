<?php

namespace App\Http\Requests;

use App\Models\DocumentFolder;
use App\Services\Udf\UdfArchiveService;
use App\Services\Udf\UdfException;
use App\Services\Udf\UdfParser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreCaseFileDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manageDocuments', $this->route('caseFile')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*' => ['required', $this->legalFileRule()],
            'folder_id' => ['nullable', Rule::exists(DocumentFolder::class, 'id')->where('case_file_id', $this->route('caseFile')->id)],
            'document_type' => ['nullable', Rule::in(['petition', 'evidence', 'notice', 'report', 'contract', 'invoice', 'enforcement', 'other'])],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function legalFileRule(): File
    {
        return File::types([
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/octet-stream',
        ])->extensions(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'udf'])->max('50mb');
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->file('documents', []) as $index => $file) {
                if (mb_strtolower($file->getClientOriginalExtension()) !== 'udf') {
                    continue;
                }

                try {
                    $archive = app(UdfArchiveService::class)->readLocal($file->getRealPath());
                    app(UdfParser::class)->parse($archive['content_xml']);
                } catch (UdfException $exception) {
                    $validator->errors()->add("documents.{$index}", $exception->getMessage());
                }
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'documents.*.extensions' => 'Dosya uzantısı desteklenmiyor. UDF yüklemek için dosya adının yalnız .udf ile bittiğini ve .udf.zip olmadığını kontrol edin.',
        ];
    }
}
