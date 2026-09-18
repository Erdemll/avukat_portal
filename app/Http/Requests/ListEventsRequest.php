<?php

namespace App\Http\Requests;

use App\EventPriority;
use App\EventStatus;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Event::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'event_type' => ['nullable', Rule::exists(EventType::class, 'id')],
            'status' => ['nullable', Rule::enum(EventStatus::class)],
            'priority' => ['nullable', Rule::enum(EventPriority::class)],
            'assigned_lawyer' => ['nullable', Rule::exists(User::class, 'id')->where(fn ($query) => $query->where('is_active', true)->whereIn('role_id', Role::query()->where('slug', 'lawyer')->select('id')))],
            'creator' => ['nullable', Rule::exists(User::class, 'id')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', Rule::in(['event_no', 'created_at', 'updated_at', 'priority'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
