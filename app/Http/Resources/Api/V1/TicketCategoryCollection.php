<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TicketCategoryCollection extends ResourceCollection
{
    public $collects = TicketCategoryResource::class;
}
