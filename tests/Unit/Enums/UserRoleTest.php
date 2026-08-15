<?php

use App\Enums\UserRole;

test('backed values are stable strings', function () {
    expect(UserRole::Customer->value)->toBe('customer')
        ->and(UserRole::SupportAgent->value)->toBe('support_agent')
        ->and(UserRole::TeamLead->value)->toBe('team_lead')
        ->and(UserRole::SupportManager->value)->toBe('support_manager')
        ->and(UserRole::Administrator->value)->toBe('administrator');
});
