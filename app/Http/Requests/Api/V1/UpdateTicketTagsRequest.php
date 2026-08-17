<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('ticket'));
    }

    public function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            'tags.*' => ['string', 'exists:tags,slug'],
        ];
    }
}
