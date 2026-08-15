<?php

use App\Enums\TicketPriority;
use App\Models\TicketCategory;

test('a ticket category casts active to boolean and default priority to an enum', function () {
    $category = TicketCategory::factory()->create([
        'active' => true,
        'default_priority' => TicketPriority::High,
    ]);

    expect($category->active)->toBeTrue()
        ->and($category->default_priority)->toBe(TicketPriority::High);
});

test('an inactive category can be created via the inactive state', function () {
    $category = TicketCategory::factory()->inactive()->create();

    expect($category->active)->toBeFalse();
});
