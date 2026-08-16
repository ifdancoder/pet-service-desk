<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SlaPolicyCollection extends ResourceCollection
{
    public $collects = SlaPolicyResource::class;
}
