<?php

namespace App\Http\Requests;

use App\EventPriority;
use App\EventStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('event')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'current_process' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(EventPriority::class)],
            'system_status' => ['nullable', Rule::enum(EventStatus::class)],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
