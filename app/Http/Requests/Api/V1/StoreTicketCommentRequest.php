<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        return $this->user()->can('create', [TicketComment::class, $ticket]);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'is_internal' => ['sometimes', 'boolean', Rule::prohibitedIf(
                fn () => $this->boolean('is_internal') && ! ($this->user()->can('ticket.view-team') || $this->user()->can('ticket.view-all'))
            )],
        ];
    }
}
