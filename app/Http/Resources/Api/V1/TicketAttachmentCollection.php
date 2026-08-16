<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketAttachmentCollection extends ResourceCollection
{
    public $collects = TicketAttachmentResource::class;
}
