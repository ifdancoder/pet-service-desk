<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;

test('a user belongs to a department', function () {
    $department = Department::factory()->create();
    $user = User::factory()->for($department)->create();

    expect($user->department->is($department))->toBeTrue();
});

test('a user can belong to multiple teams', function () {
    $user = User::factory()->create();
    $teams = Team::factory()->count(2)->create();

    $user->teams()->attach($teams);

    expect($user->teams()->pluck('teams.id')->sort()->values()->all())
        ->toBe($teams->pluck('id')->sort()->values()->all());
});
