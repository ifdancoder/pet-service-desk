<?php

namespace App\DataTransferObjects;

use App\Http\Requests\Api\V1\StoreTagRequest;
use App\Http\Requests\Api\V1\UpdateTagRequest;

final readonly class TagData
{
    public function __construct(
        public string $name,
    ) {}

    public static function fromRequest(StoreTagRequest|UpdateTagRequest $request): self
    {
        return new self(name: $request->validated('name'));
    }
}
