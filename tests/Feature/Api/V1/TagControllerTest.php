<?php

use App\Models\Tag;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('any authenticated user can list and view tags', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $tag = Tag::factory()->create();

    $this->getJson('/api/v1/tags')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/tags/{$tag->id}")->assertOk()->assertJsonPath('data.id', $tag->id);
});

test('user.manage is required to create a tag', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/tags', ['name' => 'Urgent'])->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->postJson('/api/v1/tags', ['name' => 'Urgent'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Urgent')
        ->assertJsonPath('data.slug', 'urgent');
});

test('user.manage is required to update or delete a tag', function () {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->deleteJson("/api/v1/tags/{$tag->id}")->assertForbidden();

    Permission::findOrCreate('user.manage');
    $user->givePermissionTo('user.manage');

    $this->putJson("/api/v1/tags/{$tag->id}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/v1/tags/{$tag->id}")->assertNoContent();
});
