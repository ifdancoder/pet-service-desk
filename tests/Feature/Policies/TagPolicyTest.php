<?php

use App\Models\Tag;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('every ability requires the org.manage permission', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();

    expect($user->can('viewAny', Tag::class))->toBeFalse()
        ->and($user->can('view', $tag))->toBeFalse()
        ->and($user->can('create', Tag::class))->toBeFalse()
        ->and($user->can('update', $tag))->toBeFalse()
        ->and($user->can('delete', $tag))->toBeFalse();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    expect($user->can('viewAny', Tag::class))->toBeTrue()
        ->and($user->can('view', $tag))->toBeTrue()
        ->and($user->can('create', Tag::class))->toBeTrue()
        ->and($user->can('update', $tag))->toBeTrue()
        ->and($user->can('delete', $tag))->toBeTrue();
});
