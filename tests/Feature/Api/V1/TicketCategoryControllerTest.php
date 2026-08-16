<?php

use App\Models\TicketCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('any authenticated user can list and view ticket categories', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $category = TicketCategory::factory()->create();

    $this->getJson('/api/v1/ticket-categories')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/ticket-categories/{$category->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $category->id);
});

test('user.manage is required to create a ticket category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $payload = ['name' => 'Billing', 'active' => true, 'default_priority' => 'high'];

    $this->postJson('/api/v1/ticket-categories', $payload)->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->postJson('/api/v1/ticket-categories', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Billing')
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.default_priority', 'high');
});

test('creating a ticket category requires a valid default_priority', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/ticket-categories', [
        'name' => 'Billing',
        'active' => true,
        'default_priority' => 'urgent',
    ])->assertUnprocessable();
});

test('user.manage is required to update or delete a ticket category', function () {
    $user = User::factory()->create();
    $category = TicketCategory::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->deleteJson("/api/v1/ticket-categories/{$category->id}")->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->putJson("/api/v1/ticket-categories/{$category->id}", [
        'name' => 'Renamed',
        'active' => false,
        'default_priority' => null,
    ])->assertOk()->assertJsonPath('data.active', false);

    $this->deleteJson("/api/v1/ticket-categories/{$category->id}")->assertNoContent();
});
