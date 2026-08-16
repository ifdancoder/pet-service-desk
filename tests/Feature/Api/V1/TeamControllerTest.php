<?php

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('any authenticated user can list and view teams', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $team = Team::factory()->create();

    $this->getJson('/api/v1/teams')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/teams/{$team->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $team->id);
});

test('user.manage is required to create a team', function () {
    $user = User::factory()->create();
    $department = Department::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/teams', ['name' => 'Tier 2', 'department_id' => $department->id])
        ->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->postJson('/api/v1/teams', ['name' => 'Tier 2', 'department_id' => $department->id])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Tier 2')
        ->assertJsonPath('data.department_id', $department->id);
});

test('creating a team requires a valid department_id', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/teams', ['name' => 'Tier 2', 'department_id' => 999999])
        ->assertUnprocessable();
});

test('user.manage is required to update or delete a team', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->deleteJson("/api/v1/teams/{$team->id}")->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->putJson("/api/v1/teams/{$team->id}", ['name' => 'Renamed', 'department_id' => $team->department_id])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/v1/teams/{$team->id}")->assertNoContent();
});
