<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketWatcherCollection extends ResourceCollection
{
    public $collects = TicketWatcherResource::class;
}
