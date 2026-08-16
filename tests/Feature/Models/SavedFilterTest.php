<?php

use App\Models\SavedFilter;
use App\Models\User;

test('a saved filter belongs to a user and casts filters to an array', function () {
    $user = User::factory()->create();
    $savedFilter = SavedFilter::factory()->for($user)->create([
        'name' => 'My open tickets',
        'filters' => ['status' => 'open', 'assignee_id' => $user->id],
    ]);

    expect($savedFilter->fresh()->user->is($user))->toBeTrue()
        ->and($savedFilter->fresh()->filters)->toBe(['status' => 'open', 'assignee_id' => $user->id]);
});
