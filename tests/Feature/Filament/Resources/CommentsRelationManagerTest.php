<?php

use App\Events\TicketCommented;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\RelationManagers\CommentsRelationManager;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['ticket.view-all', 'comment.create'] as $permission) {
        Permission::findOrCreate($permission);
    }
    $this->staff = User::factory()->create();
    $this->staff->givePermissionTo(['ticket.view-all', 'comment.create']);
    $this->actingAs($this->staff, 'web');
});

test('creating a comment goes through TicketCommentService and dispatches TicketCommented', function () {
    Event::fake();
    $ticket = Ticket::factory()->create();

    Livewire::test(CommentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callAction(TestAction::make('create')->table(), data: ['body' => 'Looking into this.', 'is_internal' => true])
        ->assertHasNoActionErrors();

    $comment = $ticket->comments()->first();
    expect($comment)->not->toBeNull()
        ->and($comment->body)->toBe('Looking into this.')
        ->and($comment->is_internal)->toBeTrue()
        ->and($comment->author_id)->toBe($this->staff->id);
    Event::assertDispatched(TicketCommented::class, fn ($event) => $event->comment->is($comment));
});
