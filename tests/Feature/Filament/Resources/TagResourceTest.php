<?php

use App\Enums\UserRole;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Tags\Pages\EditTag;
use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
    // support_manager, not administrator: AppServiceProvider's
    // Gate::before(administrator => true) short-circuits every Policy check, so
    // an administrator acting user would never exercise TagPolicy at all.
    $this->staff = User::factory()->create();
    $this->staff->assignRole(UserRole::SupportManager->value);
    $this->actingAs($this->staff, 'web');
});

test('lists tags', function () {
    $tags = Tag::factory()->count(3)->create();

    Livewire::test(ListTags::class)
        ->assertCanSeeTableRecords($tags);
});

test('creates a tag', function () {
    Livewire::test(CreateTag::class)
        ->fillForm(['name' => 'Urgent', 'slug' => 'urgent'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tag::where('slug', 'urgent')->exists())->toBeTrue();
});

test('edits a tag', function () {
    $tag = Tag::factory()->create();

    Livewire::test(EditTag::class, ['record' => $tag->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tag->fresh()->name)->toBe('Renamed');
});

test('a user without org.manage cannot access the tag resource', function () {
    $agent = User::factory()->create();
    $agent->assignRole(UserRole::SupportAgent->value);
    $this->actingAs($agent, 'web');

    Livewire::test(ListTags::class)->assertForbidden();
});
