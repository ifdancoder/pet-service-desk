<?php

namespace App\Services;

use App\DataTransferObjects\SlaPolicyData;
use App\Models\SlaPolicy;

class SlaPolicyService
{
    public function create(SlaPolicyData $data): SlaPolicy
    {
        return SlaPolicy::create([
            'category_id' => $data->categoryId,
            'priority' => $data->priority,
            'response_time_minutes' => $data->responseTimeMinutes,
            'resolution_time_minutes' => $data->resolutionTimeMinutes,
            'active' => $data->active,
        ]);
    }

    public function update(SlaPolicy $slaPolicy, SlaPolicyData $data): SlaPolicy
    {
        $slaPolicy->update([
            'category_id' => $data->categoryId,
            'priority' => $data->priority,
            'response_time_minutes' => $data->responseTimeMinutes,
            'resolution_time_minutes' => $data->resolutionTimeMinutes,
            'active' => $data->active,
        ]);

        return $slaPolicy;
    }

    public function delete(SlaPolicy $slaPolicy): void
    {
        $slaPolicy->delete();
    }
}
