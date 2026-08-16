<?php

namespace App\Services;

use App\DataTransferObjects\TicketCategoryData;
use App\Models\TicketCategory;
use Illuminate\Support\Str;

class TicketCategoryService
{
    public function create(TicketCategoryData $data): TicketCategory
    {
        return TicketCategory::create([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
            'active' => $data->active,
            'default_priority' => $data->defaultPriority,
        ]);
    }

    public function update(TicketCategory $category, TicketCategoryData $data): TicketCategory
    {
        $category->update([
            'name' => $data->name,
            'slug' => Str::slug($data->name),
            'active' => $data->active,
            'default_priority' => $data->defaultPriority,
        ]);

        return $category;
    }

    public function delete(TicketCategory $category): void
    {
        $category->delete();
    }
}
