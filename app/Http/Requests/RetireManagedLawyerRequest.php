<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetireManagedLawyerRequest extends FormRequest
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
            'replacement_lawyer_id' => [
                $user->isLawyer() ? 'required' : 'prohibited',
                'integer',
                Rule::exists(User::class, 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->where('id', '!=', $user->id)
                    ->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'))),
            ],
        ];
    }
}
