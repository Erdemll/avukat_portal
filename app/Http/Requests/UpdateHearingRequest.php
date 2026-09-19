<?php

namespace App\Http\Requests;

class UpdateHearingRequest extends StoreHearingRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('hearing')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'lock_version' => ['required', 'integer', 'min:0']];
    }
}
