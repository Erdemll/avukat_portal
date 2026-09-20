<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Conversation::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('user_id')) {
                return;
            }

            $target = User::query()->with('role')->find($this->integer('user_id'));

            if ($target?->id === $this->user()?->id) {
                $validator->errors()->add('user_id', 'Kendinizle mesajlaşma başlatamazsınız.');

                return;
            }

            if ($target === null || ! $target->isLawyer() || ! $target->is_active) {
                $validator->errors()->add('user_id', 'Yalnızca aktif bir avukatla mesajlaşabilirsiniz.');
            }
        }];
    }
}
