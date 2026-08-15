<?php

use App\Models\SlaPolicy;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('every ability requires the sla.manage permission', function () {
    $user = User::factory()->create();
    $policy = SlaPolicy::factory()->create();

    expect($user->can('viewAny', SlaPolicy::class))->toBeFalse()
        ->and($user->can('view', $policy))->toBeFalse()
        ->and($user->can('create', SlaPolicy::class))->toBeFalse()
        ->and($user->can('update', $policy))->toBeFalse()
        ->and($user->can('delete', $policy))->toBeFalse();

    Permission::findOrCreate('sla.manage');
    $user->givePermissionTo('sla.manage');

    expect($user->can('viewAny', SlaPolicy::class))->toBeTrue()
        ->and($user->can('view', $policy))->toBeTrue()
        ->and($user->can('create', SlaPolicy::class))->toBeTrue()
        ->and($user->can('update', $policy))->toBeTrue()
        ->and($user->can('delete', $policy))->toBeTrue();
});
