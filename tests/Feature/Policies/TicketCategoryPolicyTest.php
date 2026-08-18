<?php

use App\Models\TicketCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('every ability requires the org.manage permission', function () {
    $user = User::factory()->create();
    $ticketCategory = TicketCategory::factory()->create();

    expect($user->can('viewAny', TicketCategory::class))->toBeFalse()
        ->and($user->can('view', $ticketCategory))->toBeFalse()
        ->and($user->can('create', TicketCategory::class))->toBeFalse()
        ->and($user->can('update', $ticketCategory))->toBeFalse()
        ->and($user->can('delete', $ticketCategory))->toBeFalse();

    Permission::findOrCreate('org.manage');
    $user->givePermissionTo('org.manage');

    expect($user->can('viewAny', TicketCategory::class))->toBeTrue()
        ->and($user->can('view', $ticketCategory))->toBeTrue()
        ->and($user->can('create', TicketCategory::class))->toBeTrue()
        ->and($user->can('update', $ticketCategory))->toBeTrue()
        ->and($user->can('delete', $ticketCategory))->toBeTrue();
});
