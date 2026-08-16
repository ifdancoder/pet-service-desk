<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketCommentCollection extends ResourceCollection
{
    public $collects = TicketCommentResource::class;
}
