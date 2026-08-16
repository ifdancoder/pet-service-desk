<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('sla_policy'));
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:ticket_categories,id'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'response_time_minutes' => ['required', 'integer', 'min:1'],
            'resolution_time_minutes' => ['required', 'integer', 'min:1'],
            'active' => ['required', 'boolean'],
        ];
    }
}
