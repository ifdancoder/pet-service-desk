<?php

use App\Models\SlaPolicy;
use App\Models\TicketCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

test('sla.manage is required to list, view, create, update, or delete SLA policies', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    $policy = SlaPolicy::factory()->create();
    $category = TicketCategory::factory()->create();

    $this->getJson('/api/v1/sla-policies')->assertForbidden();
    $this->getJson("/api/v1/sla-policies/{$policy->id}")->assertForbidden();
    $this->postJson('/api/v1/sla-policies', [
        'category_id' => $category->id,
        'priority' => 'high',
        'response_time_minutes' => 60,
        'resolution_time_minutes' => 480,
        'active' => true,
    ])->assertForbidden();
    $this->putJson("/api/v1/sla-policies/{$policy->id}", [
        'category_id' => null,
        'priority' => 'low',
        'response_time_minutes' => 120,
        'resolution_time_minutes' => 960,
        'active' => true,
    ])->assertForbidden();
    $this->deleteJson("/api/v1/sla-policies/{$policy->id}")->assertForbidden();

    Permission::findOrCreate('sla.manage');
    $user->givePermissionTo('sla.manage');

    $this->getJson('/api/v1/sla-policies')->assertOk();
    $this->getJson("/api/v1/sla-policies/{$policy->id}")->assertOk();

    $created = $this->postJson('/api/v1/sla-policies', [
        'category_id' => $category->id,
        'priority' => 'high',
        'response_time_minutes' => 60,
        'resolution_time_minutes' => 480,
        'active' => true,
    ]);
    $created->assertCreated()->assertJsonPath('data.priority', 'high');

    $this->putJson("/api/v1/sla-policies/{$policy->id}", [
        'category_id' => null,
        'priority' => 'low',
        'response_time_minutes' => 120,
        'resolution_time_minutes' => 960,
        'active' => false,
    ])->assertOk()->assertJsonPath('data.active', false);

    $this->deleteJson("/api/v1/sla-policies/{$policy->id}")->assertNoContent();
});

test('creating an SLA policy requires resolution_time_minutes to be an integer', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('sla.manage');
    $user->givePermissionTo('sla.manage');
    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/sla-policies', [
        'category_id' => null,
        'priority' => 'low',
        'response_time_minutes' => 120,
        'resolution_time_minutes' => 'not-a-number',
        'active' => true,
    ])->assertUnprocessable();
});
