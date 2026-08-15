<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

test('an administrator passes any ability check via the Gate bypass', function () {
    Role::create(['name' => 'administrator']);
    $user = User::factory()->create();
    $user->assignRole('administrator');

    expect(Gate::forUser($user)->allows('some-ability-nobody-defined'))->toBeTrue();
});

test('a non-administrator does not pass an undefined ability check', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('some-ability-nobody-defined'))->toBeFalse();
});

test('a non-administrator is still denied a concretely defined, denying ability', function () {
    Gate::define('some-concrete-ability', fn () => false);

    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('some-concrete-ability'))->toBeFalse();
});

test('an administrator bypasses a concretely defined, denying ability', function () {
    Gate::define('some-concrete-ability', fn () => false);

    Role::create(['name' => 'administrator']);
    $user = User::factory()->create();
    $user->assignRole('administrator');

    expect(Gate::forUser($user)->allows('some-concrete-ability'))->toBeTrue();
});

test('a non-administrator still passes a concretely defined, allowing ability', function () {
    Gate::define('some-concrete-ability-that-allows', fn () => true);

    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('some-concrete-ability-that-allows'))->toBeTrue();
});
