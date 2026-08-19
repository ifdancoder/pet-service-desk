<?php

use App\Enums\UserRole;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    // support_manager, not administrator: AppServiceProvider's
    // Gate::before(administrator => true) short-circuits every Policy check, so
    // an administrator acting user would never exercise DepartmentPolicy at all.
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::SupportManager->value);
    $this->actingAs($this->staff, 'web');
});

test('lists departments', function () {
    $departments = Department::factory()->count(3)->create();

    Livewire::test(ListDepartments::class)
        ->assertCanSeeTableRecords($departments);
});

test('creates a department', function () {
    Livewire::test(CreateDepartment::class)
        ->fillForm(['name' => 'Billing', 'slug' => 'billing'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Department::where('slug', 'billing')->exists())->toBeTrue();
});

test('edits a department', function () {
    $department = Department::factory()->create();

    Livewire::test(EditDepartment::class, ['record' => $department->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($department->fresh()->name)->toBe('Renamed');
});

test('a user without org.manage cannot access the department resource', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(ListDepartments::class)->assertForbidden();
});
