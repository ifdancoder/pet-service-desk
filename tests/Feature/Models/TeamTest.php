<?php

use App\Models\Department;
use App\Models\Team;

test('a team belongs to a department', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();

    expect($team->department->is($department))->toBeTrue();
});
