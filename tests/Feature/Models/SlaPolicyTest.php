<?php

use App\Enums\TicketPriority;
use App\Models\SlaPolicy;
use App\Models\TicketCategory;

test('an sla policy casts priority to an enum', function () {
    $policy = SlaPolicy::factory()->create([
        'priority' => TicketPriority::Critical,
    ]);

    expect($policy->priority)->toBe(TicketPriority::Critical);
});

test('a policy can belong to a category, or be a default with no category', function () {
    $category = TicketCategory::factory()->create();
    $scoped = SlaPolicy::factory()->create(['category_id' => $category->id]);
    $default = SlaPolicy::factory()->create(['category_id' => null]);

    expect($scoped->category->is($category))->toBeTrue()
        ->and($default->category)->toBeNull();
});

test('a category has many sla policies', function () {
    $category = TicketCategory::factory()->create();
    $policy = SlaPolicy::factory()->create(['category_id' => $category->id]);

    expect($category->slaPolicies()->pluck('id')->all())->toBe([$policy->id]);
});
