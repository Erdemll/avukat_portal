<?php

namespace App\Http\Requests;

use App\Services\Udf\UdfArchiveService;
use App\Services\Udf\UdfException;
use App\Services\Udf\UdfParser;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreDocumentVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('uploadVersion', $this->route('document')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', File::types([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'application/zip',
                'application/octet-stream',
            ])->extensions(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'udf'])->max('50mb')],
            'change_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $file = $this->file('file');

            if ($file === null || mb_strtolower($file->getClientOriginalExtension()) !== 'udf') {
                return;
            }

            try {
                $archive = app(UdfArchiveService::class)->readLocal($file->getRealPath());
                app(UdfParser::class)->parse($archive['content_xml']);
            } catch (UdfException $exception) {
                $validator->errors()->add('file', $exception->getMessage());
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'file.extensions' => 'Dosya uzantısı desteklenmiyor. UDF yüklemek için dosya adının yalnız .udf ile bittiğini ve .udf.zip olmadığını kontrol edin.',
        ];
    }
}
