<?php

namespace App\Services;

use App\DataTransferObjects\TeamData;
use App\Models\Team;
use Illuminate\Support\Str;

class TeamService
{
    public function create(TeamData $data): Team
    {
        return Team::create([
            'department_id' => $data->departmentId,
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);
    }

    public function update(Team $team, TeamData $data): Team
    {
        $team->update([
            'department_id' => $data->departmentId,
            'name' => $data->name,
            'slug' => Str::slug($data->name),
        ]);

        return $team;
    }

    public function delete(Team $team): void
    {
        $team->delete();
    }
}
