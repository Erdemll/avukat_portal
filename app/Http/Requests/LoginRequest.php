<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required_without:sicil_no', 'string', 'email'],
            'sicil_no' => [
                'nullable',
                'required_without:email',
                'string',
                'size:11',
                'regex:/^[1-9][0-9]{10}$/',
            ],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function isLawyerLogin(): bool
    {
        return $this->filled('sicil_no');
    }
}
