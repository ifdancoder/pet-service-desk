<?php

use App\Events\TicketCommented;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\RelationManagers\CommentsRelationManager;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\TicketCommentService;
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

test('deleting a comment goes through TicketCommentService', function () {
    // The action is routed through the service rather than Filament's default
    // Eloquent delete, matching AttachmentsRelationManager and the plan's
    // "all writes go through a service" constraint. A recording subclass proves
    // the routing without mocking out the real deletion.
    $recorder = new class extends TicketCommentService
    {
        /** @var array<int, int> */
        public array $deletedIds = [];

        public function delete(TicketComment $comment): void
        {
            $this->deletedIds[] = $comment->id;

            parent::delete($comment);
        }
    };
    app()->instance(TicketCommentService::class, $recorder);

    // Guard pinned explicitly: actingAs(..., 'web') in beforeEach has already
    // mutated config('auth.defaults.guard'), so an unqualified findOrCreate()
    // here would create the row under the wrong guard (see Fix 9 / User::GUARD_NAME).
    Permission::findOrCreate('comment.delete-any', User::GUARD_NAME);
    $this->staff->givePermissionTo('comment.delete-any');

    $ticket = Ticket::factory()->create();
    $comment = TicketComment::factory()->for($ticket)->create();

    Livewire::test(CommentsRelationManager::class, [
        'ownerRecord' => $ticket,
        'pageClass' => EditTicket::class,
    ])
        ->callTableAction('delete', $comment)
        ->assertHasNoTableActionErrors();

    expect($recorder->deletedIds)->toBe([$comment->id])
        ->and(TicketComment::find($comment->id))->toBeNull();
});
