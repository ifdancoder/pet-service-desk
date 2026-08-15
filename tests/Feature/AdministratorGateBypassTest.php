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
