<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('createDocument', $this->route('event')) ?? false;
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
            'documents.*' => [
                'required',
                File::types([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/svg+xml',
                    'image/bmp',
                    'image/tiff',
                    'audio/mpeg',
                    'audio/wav',
                    'audio/ogg',
                    'audio/mp4',
                    'audio/x-m4a',
                    'audio/aac',
                    'video/mp4',
                    'video/x-msvideo',
                    'video/quicktime',
                    'video/x-matroska',
                    'video/webm',
                    'video/ogg',
                    'text/plain',
                    'text/rtf',
                    'application/rtf',
                ])->extensions([
                    'pdf', 'doc', 'docx', 'xls', 'xlsx',
                    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif',
                    'mp3', 'wav', 'ogg', 'm4a', 'aac',
                    'mp4', 'avi', 'mov', 'mkv', 'webm',
                    'txt', 'rtf',
                ])->max('50mb'),
            ],
        ];
    }
}
