<?php

use App\Models\SavedFilter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a user only sees their own saved filters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    SavedFilter::factory()->for($user)->create();
    SavedFilter::factory()->create();

    $this->getJson('/api/v1/saved-filters')->assertOk()->assertJsonCount(1, 'data');
});

test('any authenticated user can create a saved filter for themselves', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/saved-filters', [
        'name' => 'My open tickets',
        'filters' => ['status' => 'open', 'priority' => 'high'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'My open tickets')
        ->assertJsonPath('data.filters.status', 'open');

    expect(SavedFilter::first()->user_id)->toBe($user->id);
});

test('creating a saved filter validates the filters payload against the same whitelist', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/saved-filters', [
        'name' => 'Bad filter',
        'filters' => ['status' => 'not-a-real-status'],
    ])->assertUnprocessable();
});

test('viewing, updating, or deleting another user\'s saved filter is not found', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $savedFilter = SavedFilter::factory()->for($owner)->create();

    $this->getJson("/api/v1/saved-filters/{$savedFilter->id}")->assertNotFound();
    $this->putJson("/api/v1/saved-filters/{$savedFilter->id}", [
        'name' => 'Hijacked',
        'filters' => ['status' => 'open'],
    ])->assertNotFound();
    $this->deleteJson("/api/v1/saved-filters/{$savedFilter->id}")->assertNotFound();
});

test('a user can update and delete their own saved filter', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $savedFilter = SavedFilter::factory()->for($user)->create();

    $this->putJson("/api/v1/saved-filters/{$savedFilter->id}", [
        'name' => 'Renamed',
        'filters' => ['priority' => 'critical'],
    ])->assertOk()->assertJsonPath('data.name', 'Renamed');

    $this->deleteJson("/api/v1/saved-filters/{$savedFilter->id}")->assertNoContent();
    expect(SavedFilter::find($savedFilter->id))->toBeNull();
});

test('a partial PATCH with only name preserves the existing filters', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $savedFilter = SavedFilter::factory()->for($user)->create([
        'name' => 'Original name',
        'filters' => ['status' => 'open', 'priority' => 'high'],
    ]);

    $response = $this->patchJson("/api/v1/saved-filters/{$savedFilter->id}", [
        'name' => 'renamed',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.name', 'renamed')
        ->assertJsonPath('data.filters.status', 'open')
        ->assertJsonPath('data.filters.priority', 'high');

    expect($savedFilter->fresh())
        ->name->toBe('renamed')
        ->filters->toBe(['status' => 'open', 'priority' => 'high']);
});
