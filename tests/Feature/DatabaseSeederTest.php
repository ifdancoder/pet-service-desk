<?php

use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('the database seeder produces a coherent demo dataset', function () {
    Artisan::call('db:seed');

    expect(Department::count())->toBe(3)
        ->and(Team::count())->toBe(6)
        ->and(User::count())->toBe(16)
        ->and(TicketCategory::count())->toBe(5)
        ->and(SlaPolicy::count())->toBe(20)
        ->and(Tag::count())->toBe(8)
        ->and(Ticket::count())->toBe(40);

    expect(User::where('email', 'test@example.com')->value('department_id'))->not->toBeNull();
});
