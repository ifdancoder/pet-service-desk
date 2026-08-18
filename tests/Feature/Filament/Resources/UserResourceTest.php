<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::Administrator->value);
    $this->actingAs($this->staff, 'web');
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
