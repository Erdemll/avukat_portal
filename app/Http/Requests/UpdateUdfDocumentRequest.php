<?php

namespace App\Http\Requests;

use App\Services\Udf\UdfContentValidator;
use App\Services\Udf\UdfException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateUdfDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('editUdf', $this->route('document')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_version' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'array:type,content'],
            'content.type' => ['required', 'in:doc'],
            'content.content' => ['required', 'array', 'min:1'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('content') || ! is_array($this->input('content'))) {
                return;
            }

            try {
                app(UdfContentValidator::class)->validate($this->input('content'));
            } catch (UdfException $exception) {
                $validator->errors()->add('content', $exception->getMessage());
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'document_version.required' => 'Düzenlenen belge sürümü belirtilmelidir.',
            'content.required' => 'Editör içeriği gönderilmelidir.',
            'content.array' => 'Editör içeriği geçerli JSON belge yapısında olmalıdır.',
            'content.type.in' => 'Editör içeriğinin kök düğümü doc olmalıdır.',
        ];
    }
}
