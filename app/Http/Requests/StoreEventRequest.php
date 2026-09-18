<?php

namespace App\Http\Requests;

use App\EventPriority;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_type_id' => ['required', Rule::exists(EventType::class, 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'assigned_lawyer_id' => ['required', Rule::exists(User::class, 'id')->where(function ($query): void {
                $query->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id'));
            })],
            'priority' => ['required', Rule::enum(EventPriority::class)],
            'occurred_at' => ['nullable', 'date'],
            'current_process' => ['nullable', 'string'],
        ];
    }
}
