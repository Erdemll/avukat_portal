<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManagedUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'tc_kimlik_no' => [
                $this->isLawyerRoleSelected() ? 'required' : 'nullable',
                'string',
                'size:11',
                'regex:/^[1-9][0-9]{10}$/',
                Rule::unique(User::class)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_active', true)],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function isLawyerRoleSelected(): bool
    {
        $roleId = $this->integer('role_id');

        return Role::query()->where('id', $roleId)->where('slug', 'lawyer')->exists();
    }
}
