<?php

use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('every ability requires the org.manage permission', function () {
    $user = User::factory()->create();
    $department = Department::factory()->create();

    expect($user->can('viewAny', Department::class))->toBeFalse()
        ->and($user->can('view', $department))->toBeFalse()
        ->and($user->can('create', Department::class))->toBeFalse()
        ->and($user->can('update', $department))->toBeFalse()
        ->and($user->can('delete', $department))->toBeFalse();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    expect($user->can('viewAny', Department::class))->toBeTrue()
        ->and($user->can('view', $department))->toBeTrue()
        ->and($user->can('create', Department::class))->toBeTrue()
        ->and($user->can('update', $department))->toBeTrue()
        ->and($user->can('delete', $department))->toBeTrue();
});
