<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    // No role is assigned at all, and 'user.manage' is granted directly.
    // Assigning the administrator role would trip AppServiceProvider's
    // Gate::before(administrator => true), short-circuiting UserPolicy so it is
    // never exercised; and no non-administrator role holds 'user.manage' by
    // design, so a role-based acting user could not succeed here either.
    $this->staff = User::factory()->create();
    Permission::findOrCreate('user.manage');
    $this->staff->givePermissionTo('user.manage');
    $this->actingAs($this->staff, 'web');
});

test('a user without user.manage cannot access the user resource', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(ListUsers::class)->assertForbidden();
});

test('the create ability on users is gated on user.manage', function () {
    // UserPolicy::create() used to be absent entirely. With
    // checkPolicyExistence off, Filament treats a missing policy method as
    // allow-by-default, so the create page was reachable for anyone who passed
    // the resource's viewAny gate. Asserting through the Gate isolates the
    // method itself: with no UserPolicy::create(), even the holder is denied
    // here, so both directions pin the method's presence and its rule.
    expect($this->staff->can('create', User::class))->toBeTrue();

    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);

    expect($agent->can('create', User::class))->toBeFalse();
});

test('a user without user.manage cannot reach the create user page', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(CreateUser::class)->assertForbidden();
});

test('lists users', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users->push($this->staff));
});

test('creates a user with a role', function () {
    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'New Agent',
            'email' => 'new-agent@example.com',
            'password' => 'password',
            'roles' => [UserRole::SupportAgent->value],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::where('email', 'new-agent@example.com')->firstOrFail();
    expect($created->hasRole(UserRole::SupportAgent->value))->toBeTrue();
});

test('edits a user and changes their roles', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRole::SupportAgent->value);

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->fillForm(['roles' => [UserRole::TeamLead->value]])
        ->call('save')
        ->assertHasNoFormErrors();

    $user->refresh();
    expect($user->hasRole(UserRole::TeamLead->value))->toBeTrue()
        ->and($user->hasRole(UserRole::SupportAgent->value))->toBeFalse();
});
