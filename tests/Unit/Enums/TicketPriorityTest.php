<?php

use App\Enums\TicketPriority;

test('backed values are stable strings', function () {
    expect(TicketPriority::Low->value)->toBe('low')
        ->and(TicketPriority::Normal->value)->toBe('normal')
        ->and(TicketPriority::High->value)->toBe('high')
        ->and(TicketPriority::Critical->value)->toBe('critical');
});
