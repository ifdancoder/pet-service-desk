<?php

use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Filament\Resources\TicketCategories\Pages\CreateTicketCategory;
use App\Filament\Resources\TicketCategories\Pages\EditTicketCategory;
use App\Filament\Resources\TicketCategories\Pages\ListTicketCategories;
use App\Models\TicketCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    // support_manager, not administrator: AppServiceProvider's
    // Gate::before(administrator => true) short-circuits every Policy check, so
    // an administrator acting user would never exercise TicketCategoryPolicy at all.
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::SupportManager->value);
    $this->actingAs($this->staff, 'web');
});

test('lists ticket categories', function () {
    $categories = TicketCategory::factory()->count(3)->create();

    Livewire::test(ListTicketCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('creates a ticket category', function () {
    Livewire::test(CreateTicketCategory::class)
        ->fillForm([
            'name' => 'Hardware',
            'slug' => 'hardware',
            'active' => true,
            'default_priority' => TicketPriority::Normal->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TicketCategory::where('slug', 'hardware')->exists())->toBeTrue();
});

test('edits a ticket category', function () {
    $category = TicketCategory::factory()->create();

    Livewire::test(EditTicketCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->active)->toBeFalse();
});

test('a user without org.manage cannot access the ticket category resource', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(ListTicketCategories::class)->assertForbidden();
});
