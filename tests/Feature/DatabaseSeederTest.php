<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\SlaPolicy;
use App\Models\Tag;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('the database seeder produces a coherent demo dataset', function () {
    Artisan::call('db:seed');

    expect(Department::count())->toBe(3)
        ->and(Team::count())->toBe(6)
        ->and(User::count())->toBe(36)
        ->and(TicketCategory::count())->toBe(5)
        ->and(SlaPolicy::count())->toBe(24)
        ->and(Tag::count())->toBe(8)
        ->and(Ticket::count())->toBe(40)
        ->and(Role::count())->toBe(10)
        ->and(Permission::count())->toBe(18);

    $testUser = User::where('email', 'test@example.com')->first();
    expect($testUser->department_id)->not->toBeNull()
        ->and($testUser->hasRole(UserRole::Administrator->value))->toBeTrue();

    expect(User::role(UserRole::Customer->value)->count())->toBe(20);
});
