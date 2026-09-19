<?php

namespace App\Http\Requests;

class UpdateClientCommunicationRequest extends StoreClientCommunicationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('clientCommunication')) ?? false;
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
