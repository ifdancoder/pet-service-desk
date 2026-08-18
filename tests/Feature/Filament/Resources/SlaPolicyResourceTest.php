<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Filament\Resources\SlaPolicies\Pages\CreateSlaPolicy;
use App\Filament\Resources\SlaPolicies\Pages\EditSlaPolicy;
use App\Filament\Resources\SlaPolicies\Pages\ListSlaPolicies;
use App\Models\SlaPolicy;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::Administrator->value);
    $this->actingAs($this->staff, 'web');
});

test('lists sla policies', function () {
    $policies = SlaPolicy::factory()->count(3)->create();

    Livewire::test(ListSlaPolicies::class)
        ->assertCanSeeTableRecords($policies);
});

test('creates an sla policy with no category (default policy)', function () {
    Livewire::test(CreateSlaPolicy::class)
        ->fillForm([
            'priority' => TicketPriority::High->value,
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 480,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SlaPolicy::where('priority', TicketPriority::High)->whereNull('category_id')->exists())->toBeTrue();
});

test('creates an sla policy scoped to a category', function () {
    $category = TicketCategory::factory()->create();

    Livewire::test(CreateSlaPolicy::class)
        ->fillForm([
            'category_id' => $category->id,
            'priority' => TicketPriority::Critical->value,
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
            'active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(SlaPolicy::where('category_id', $category->id)->exists())->toBeTrue();
});

test('edits an sla policy', function () {
    $policy = SlaPolicy::factory()->create(['active' => true]);

    Livewire::test(EditSlaPolicy::class, ['record' => $policy->getRouteKey()])
        ->fillForm(['active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($policy->fresh()->active)->toBeFalse();
});

test('a user without sla.manage cannot access the sla policy resource', function () {
    $agent = User::factory()->create();
    Permission::findOrCreate('ticket.manage');
    $agent->givePermissionTo('ticket.manage');
    $this->actingAs($agent, 'web');

    Livewire::test(ListSlaPolicies::class)->assertForbidden();
});
