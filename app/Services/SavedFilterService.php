<?php

namespace App\Services;

use App\DataTransferObjects\SavedFilterData;
use App\Models\SavedFilter;
use App\Models\User;

class SavedFilterService
{
    public function create(SavedFilterData $data, User $owner): SavedFilter
    {
        return SavedFilter::create([
            'user_id' => $owner->id,
            'name' => $data->name,
            'filters' => $data->filters,
        ]);
    }

    public function update(SavedFilter $savedFilter, SavedFilterData $data): SavedFilter
    {
        $savedFilter->update([
            'name' => $data->name,
            'filters' => $data->filters,
        ]);

        return $savedFilter;
    }

    public function delete(SavedFilter $savedFilter): void
    {
        $savedFilter->delete();
    }
}
