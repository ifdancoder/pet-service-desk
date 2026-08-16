<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SavedFilterCollection extends ResourceCollection
{
    public $collects = SavedFilterResource::class;
}
