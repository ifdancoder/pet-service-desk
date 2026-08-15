<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('a user can be assigned a role and the assignment persists', function () {
    Role::create(['name' => 'tester']);
    $user = User::factory()->create();

    $user->assignRole('tester');

    expect($user->fresh()->hasRole('tester'))->toBeTrue();
});
