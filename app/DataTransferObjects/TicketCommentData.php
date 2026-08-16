<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreTicketCommentRequest;

final readonly class TicketCommentData
{
    public function __construct(
        public string $body,
        public bool $isInternal,
    ) {}

    public static function fromRequest(StoreTicketCommentRequest $request): self
    {
        return new self(
            body: $request->validated('body'),
            isInternal: (bool) ($request->validated('is_internal') ?? false),
        );
    }
}
