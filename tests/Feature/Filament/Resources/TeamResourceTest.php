<?php

use App\Enums\UserRole;
use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\Pages\ListTeams;
use App\Filament\Resources\Teams\RelationManagers\UsersRelationManager;
use App\Models\Department;
use App\Models\Team;
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

test('lists teams', function () {
    $teams = Team::factory()->count(3)->create();

    Livewire::test(ListTeams::class)
        ->assertCanSeeTableRecords($teams);
});

test('creates a team', function () {
    $department = Department::factory()->create();

    Livewire::test(CreateTeam::class)
        ->fillForm([
            'department_id' => $department->id,
            'name' => 'Onboarding',
            'slug' => 'onboarding',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Team::where('slug', 'onboarding')->exists())->toBeTrue();
});

test('edits a team', function () {
    $team = Team::factory()->create();

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($team->fresh()->name)->toBe('Renamed');
});

test('attaches a member to a team via the relation manager', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $team,
        'pageClass' => EditTeam::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $member->id])
        ->assertHasNoTableActionErrors();

    expect($team->users()->whereKey($member->id)->exists())->toBeTrue();
});
