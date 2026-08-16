<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Storage::fake('s3');
});

test('listing attachments requires view access to the ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    $othersTicket = Ticket::factory()->create();
    TicketAttachment::factory()->for($ownTicket)->create(['disk' => 's3']);

    $this->getJson("/api/v1/tickets/{$ownTicket->id}/attachments")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/tickets/{$othersTicket->id}/attachments")->assertForbidden();
});

test("uploading an attachment requires update access to the user's own open ticket", function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $file = UploadedFile::fake()->create('screenshot.png', 500, 'image/png');

    $response = $this->postJson("/api/v1/tickets/{$ownOpenTicket->id}/attachments", ['file' => $file]);

    $response->assertCreated()
        ->assertJsonPath('data.original_name', 'screenshot.png')
        ->assertJsonPath('data.mime_type', 'image/png');

    $attachment = TicketAttachment::first();
    Storage::disk('s3')->assertExists($attachment->path);

    $othersTicket = Ticket::factory()->create(['status' => TicketStatus::Open]);
    $this->postJson("/api/v1/tickets/{$othersTicket->id}/attachments", ['file' => $file])
        ->assertForbidden();
});

test('uploading rejects a file over the size limit', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    $tooLarge = UploadedFile::fake()->create('huge.pdf', 10241, 'application/pdf');

    $this->postJson("/api/v1/tickets/{$ownOpenTicket->id}/attachments", ['file' => $tooLarge])
        ->assertUnprocessable();
});

test('deleting an attachment requires update access to the ticket', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.update-own');
    $user->givePermissionTo('ticket.update-own');
    Sanctum::actingAs($user, ['*']);

    $ownOpenTicket = Ticket::factory()->create([
        'requester_id' => $user->id,
        'status' => TicketStatus::Open,
    ]);
    Storage::disk('s3')->put('tickets/1/existing.png', 'contents');
    $attachment = TicketAttachment::factory()->for($ownOpenTicket)->create([
        'disk' => 's3',
        'path' => 'tickets/1/existing.png',
    ]);

    $this->deleteJson("/api/v1/tickets/{$ownOpenTicket->id}/attachments/{$attachment->id}")
        ->assertNoContent();

    Storage::disk('s3')->assertMissing('tickets/1/existing.png');
    expect(TicketAttachment::find($attachment->id))->toBeNull();
});

test('downloading an attachment redirects to a temporary URL', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('ticket.view-own');
    $user->givePermissionTo('ticket.view-own');
    Sanctum::actingAs($user, ['*']);

    $ownTicket = Ticket::factory()->create(['requester_id' => $user->id]);
    Storage::disk('s3')->put('tickets/1/report.pdf', 'contents');
    $attachment = TicketAttachment::factory()->for($ownTicket)->create([
        'disk' => 's3',
        'path' => 'tickets/1/report.pdf',
    ]);

    $this->get("/api/v1/tickets/{$ownTicket->id}/attachments/{$attachment->id}/download")
        ->assertRedirect();
});
