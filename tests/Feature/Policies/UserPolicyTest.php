<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('viewAny requires user.manage', function () {
    $user = User::factory()->create();

    expect($user->can('viewAny', User::class))->toBeFalse();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    expect($user->can('viewAny', User::class))->toBeTrue();
});

test('a user can always view and update themselves without user.manage', function () {
    $user = User::factory()->create();

    expect($user->can('view', $user))->toBeTrue()
        ->and($user->can('update', $user))->toBeTrue();
});

test('user.manage allows viewing and updating any other user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->can('view', $other))->toBeFalse()
        ->and($user->can('update', $other))->toBeFalse();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    expect($user->can('view', $other))->toBeTrue()
        ->and($user->can('update', $other))->toBeTrue();
});

test('delete requires user.manage and cannot target self', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    expect($user->can('delete', $other))->toBeTrue()
        ->and($user->can('delete', $user))->toBeFalse();
});
