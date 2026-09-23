<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactivateManagedUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'email' => [$user->isLawyer() ? 'required' : 'prohibited', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'tc_kimlik_no' => [
                $user->isLawyer() ? 'required' : 'prohibited',
                'string',
                'size:11',
                'regex:/^[1-9][0-9]{10}$/',
                Rule::unique(User::class)->ignore($user),
            ],
        ];
    }
}
