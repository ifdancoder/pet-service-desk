<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Permission;

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

test('staff users returns an empty collection when no ticket-view permissions are seeded', function () {
    $department = Department::factory()->create();
    User::factory()->for($department)->create();

    expect($department->staffUsers())->toBeEmpty();
});

test('staff users still works when only one of the two permission names is seeded', function () {
    $department = Department::factory()->create();
    Permission::findOrCreate('ticket.view-team');
    $staffMember = User::factory()->for($department)->create();
    $staffMember->givePermissionTo('ticket.view-team');

    $staff = $department->staffUsers();

    expect($staff->pluck('id')->all())->toBe([$staffMember->id]);
});
