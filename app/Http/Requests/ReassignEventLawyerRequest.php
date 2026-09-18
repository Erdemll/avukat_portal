<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReassignEventLawyerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isManager() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_lawyer_id' => ['required', Rule::exists(User::class, 'id')->where(fn ($q) => $q->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id')))],
        ];
    }
}
