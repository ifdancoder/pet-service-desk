<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Ticket::class);
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
            'category_id' => ['required', 'integer', Rule::exists('ticket_categories', 'id')->where('active', true)],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id', Rule::prohibitedIf(fn () => ! $this->user()->can('ticket.assign'))],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id', Rule::prohibitedIf(fn () => ! $this->user()->can('ticket.assign'))],
        ];
    }
}
