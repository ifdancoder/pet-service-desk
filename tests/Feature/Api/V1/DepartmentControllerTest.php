<?php

use App\Models\Department;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('any authenticated user can list and view departments', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $department = Department::factory()->create();

    $this->getJson('/api/v1/departments')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/departments/{$department->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $department->id);
});

test('listing departments requires authentication', function () {
    $this->getJson('/api/v1/departments')->assertUnauthorized();
});

test('org.manage is required to create a department', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/departments', ['name' => 'Support'])->assertForbidden();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    $response = $this->postJson('/api/v1/departments', ['name' => 'Support']);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Support')
        ->assertJsonPath('data.slug', 'support');
});

test('creating a department requires a name', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/departments', [])->assertUnprocessable();
});

test('org.manage is required to update or delete a department', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $department = Department::factory()->create();

    $this->putJson("/api/v1/departments/{$department->id}", ['name' => 'Renamed'])->assertForbidden();
    $this->deleteJson("/api/v1/departments/{$department->id}")->assertForbidden();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    $this->putJson("/api/v1/departments/{$department->id}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed')
        ->assertJsonPath('data.slug', 'renamed');

    $this->deleteJson("/api/v1/departments/{$department->id}")->assertNoContent();
    expect(Department::find($department->id))->toBeNull();
});
