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
    // support_manager, not administrator: AppServiceProvider's
    // Gate::before(administrator => true) short-circuits every Policy check,
    // so an administrator acting user would never exercise TeamPolicy at all.
    // support_manager holds 'org.manage' legitimately, and notably NOT
    // 'user.manage', which the members relation manager must not require.
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::SupportManager->value);
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

test('the members tab is visible to an org.manage holder without user.manage', function () {
    // Regression: canViewForRecord() defaulted to UserPolicy::viewAny()
    // ('user.manage', administrator-only) instead of the Team resource's own
    // 'org.manage' gate, hiding the tab from support managers.
    expect($this->staff->can('user.manage'))->toBeFalse()
        ->and($this->staff->can('org.manage'))->toBeTrue();

    $team = Team::factory()->create();

    expect(UsersRelationManager::canViewForRecord($team, EditTeam::class))->toBeTrue();

    Livewire::test(UsersRelationManager::class, [
        'ownerRecord' => $team,
        'pageClass' => EditTeam::class,
    ])->assertSuccessful();
});

test('a user without org.manage cannot access the team resource', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(ListTeams::class)->assertForbidden();
});

test('a user without org.manage cannot access the team members tab', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    $team = Team::factory()->create();

    // canViewForRecord() is what the owning Edit page consults to decide
    // whether to render the tab at all (HasRelationManagers::getRelationManagers()).
    expect(UsersRelationManager::canViewForRecord($team, EditTeam::class))->toBeFalse();
});
