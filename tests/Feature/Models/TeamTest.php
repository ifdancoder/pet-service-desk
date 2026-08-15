<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;

test('a team belongs to a department', function () {
    $department = Department::factory()->create();
    $team = Team::factory()->for($department)->create();

    expect($team->department->is($department))->toBeTrue();
});

test('a team has many users', function () {
    $team = Team::factory()->create();
    $users = User::factory()->count(2)->create();

    $team->users()->attach($users);

    expect($team->users()->pluck('users.id')->sort()->values()->all())
        ->toBe($users->pluck('id')->sort()->values()->all());
});
