<?php

use App\Enums\UserRole;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => RolePermissionSeeder::class]);
});

test('an administrator can see audit log entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Administrator->value);
    $this->actingAs($admin, 'web');

    $ticket = Ticket::factory()->create(['subject' => 'Old subject']);
    $ticket->update(['subject' => 'New subject']);
    $log = AuditLog::first();

    Livewire::test(ListAuditLogs::class)
        ->assertCanSeeTableRecords([$log]);
});

test('a non-administrator cannot access the audit log resource', function () {
    $manager = User::factory()->create();
    $manager->assignRole(UserRole::SupportManager->value);
    $this->actingAs($manager, 'web');

    Livewire::test(ListAuditLogs::class)->assertForbidden();
});
