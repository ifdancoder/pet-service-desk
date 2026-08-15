<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;

test('a department has many teams', function () {
    $department = Department::factory()->create();
    $teams = Team::factory()->count(2)->for($department)->create();

    expect($department->teams()->pluck('id')->sort()->values()->all())
        ->toBe($teams->pluck('id')->sort()->values()->all());
});

test('a department has many users', function () {
    $department = Department::factory()->create();
    $users = User::factory()->count(2)->for($department)->create();

    expect($department->users()->pluck('id')->sort()->values()->all())
        ->toBe($users->pluck('id')->sort()->values()->all());
});
