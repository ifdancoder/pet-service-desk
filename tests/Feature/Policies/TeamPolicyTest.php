<?php

use App\Models\Team;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('every ability requires the org.manage permission', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();

    expect($user->can('viewAny', Team::class))->toBeFalse()
        ->and($user->can('view', $team))->toBeFalse()
        ->and($user->can('create', Team::class))->toBeFalse()
        ->and($user->can('update', $team))->toBeFalse()
        ->and($user->can('delete', $team))->toBeFalse();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    expect($user->can('viewAny', Team::class))->toBeTrue()
        ->and($user->can('view', $team))->toBeTrue()
        ->and($user->can('create', Team::class))->toBeTrue()
        ->and($user->can('update', $team))->toBeTrue()
        ->and($user->can('delete', $team))->toBeTrue();
});
