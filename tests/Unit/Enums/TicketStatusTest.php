<?php

use App\Enums\TicketStatus;

test('closed status reports itself as closed', function () {
    expect(TicketStatus::Closed->isClosed())->toBeTrue();
});

test('non-closed statuses report themselves as not closed', function () {
    expect(TicketStatus::Open->isClosed())->toBeFalse();
    expect(TicketStatus::InProgress->isClosed())->toBeFalse();
    expect(TicketStatus::OnHold->isClosed())->toBeFalse();
    expect(TicketStatus::Resolved->isClosed())->toBeFalse();
});

test('backed values are stable strings', function () {
    expect(TicketStatus::Open->value)->toBe('open')
        ->and(TicketStatus::InProgress->value)->toBe('in_progress')
        ->and(TicketStatus::OnHold->value)->toBe('on_hold')
        ->and(TicketStatus::Resolved->value)->toBe('resolved')
        ->and(TicketStatus::Closed->value)->toBe('closed');
});
