<?php

use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\RelationManagers\AttachmentsRelationManager;
use App\Jobs\ScanAttachment;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('ticket.view-all');
    $this->staff = User::factory()->create();
    $this->staff->givePermissionTo('ticket.view-all');
    $this->actingAs($this->staff, 'web');
});

test('uploading an attachment goes through TicketAttachmentService and dispatches ScanAttachment', function () {
    Queue::fake();
    Storage::fake('s3');
    $ticket = Ticket::factory()->create();
    $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callAction(TestAction::make('upload')->table(), data: ['file' => $file])
        ->assertHasNoActionErrors();

    $attachment = $ticket->attachments()->first();
    expect($attachment)->not->toBeNull()
        ->and($attachment->uploader_id)->toBe($this->staff->id);
    Queue::assertPushed(ScanAttachment::class, fn ($job) => $job->attachment->is($attachment));
});

test('rejects an oversized upload, matching the API request rules', function () {
    Storage::fake('s3');
    $ticket = Ticket::factory()->create();
    // 11 MB: over StoreTicketAttachmentRequest's max:10240 (KB) but still under
    // Livewire's own 12 MB temporary-upload ceiling, so the rejection can only
    // come from the field's maxSize() rule.
    $file = UploadedFile::fake()->create('huge.pdf', 11264, 'application/pdf');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callAction(TestAction::make('upload')->table(), data: ['file' => $file])
        ->assertHasActionErrors(['file']);

    expect($ticket->attachments()->count())->toBe(0);
});

test('rejects a disallowed file type, matching the API request rules', function () {
    Storage::fake('s3');
    $ticket = Ticket::factory()->create();
    $file = UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload');

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callAction(TestAction::make('upload')->table(), data: ['file' => $file])
        ->assertHasActionErrors(['file']);

    expect($ticket->attachments()->count())->toBe(0);
});

test('deleting an attachment goes through TicketAttachmentService', function () {
    Storage::fake('s3');
    $ticket = Ticket::factory()->create();
    Storage::disk('s3')->put('tickets/1/file.pdf', 'contents');
    $attachment = TicketAttachment::factory()->for($ticket)->create(['disk' => 's3', 'path' => 'tickets/1/file.pdf']);

    Livewire::test(AttachmentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callTableAction('delete', $attachment)
        ->assertHasNoTableActionErrors();

    expect(TicketAttachment::find($attachment->id))->toBeNull();
    Storage::disk('s3')->assertMissing('tickets/1/file.pdf');
});
