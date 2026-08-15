<?php

use App\Models\Department;
use App\Models\Team;

test('a department has many teams', function () {
    $department = Department::factory()->create();
    $teams = Team::factory()->count(2)->for($department)->create();

    expect($department->teams()->pluck('id')->sort()->values()->all())
        ->toBe($teams->pluck('id')->sort()->values()->all());
});
