<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
});

test('staff roles can access the admin panel', function (UserRole $role) {
    $user = User::factory()->create();
    $user->assignRole($role->value);

    $response = $this->actingAs($user, 'web')->get('/admin');

    $response->assertOk();
})->with([
    UserRole::SupportAgent,
    UserRole::TeamLead,
    UserRole::SupportManager,
    UserRole::Administrator,
]);

test('a customer cannot access the admin panel', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::Customer->value);

    $response = $this->actingAs($user, 'web')->get('/admin');

    $response->assertForbidden();
});

test('a user with no role cannot access the admin panel', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')->get('/admin');

    $response->assertForbidden();
});
