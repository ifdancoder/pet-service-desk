<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Concerns\HasAuditLog;
use App\Observers\TicketSearchObserver;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable([
    'requester_id',
    'assignee_id',
    'category_id',
    'department_id',
    'team_id',
    'subject',
    'description',
    'status',
    'priority',
    'sla_due_at',
    'resolved_at',
    'closed_at',
])]
#[ObservedBy(TicketSearchObserver::class)]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, HasAuditLog;

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'sla_due_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticket_watchers');
    }

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(SlaViolation::class);
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', '!=', TicketStatus::Closed);
    }

    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->where('status', TicketStatus::Closed);
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->can('ticket.view-all')) {
            return;
        }

        $canViewTeam = $user->can('ticket.view-team');
        $canViewOwn = $user->can('ticket.view-own');

        if (! $canViewTeam && ! $canViewOwn) {
            // An empty nested where-group compiles away entirely (Laravel drops
            // it from the SQL), which would otherwise match every ticket. Force
            // zero results when the user holds none of the view permissions.
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $query) use ($canViewTeam, $canViewOwn, $user) {
            if ($canViewTeam) {
                $query->orWhereIn('team_id', $user->teams->pluck('id'));
            }

            if ($canViewOwn) {
                $query->orWhere('requester_id', $user->id);
            }
        });
    }
}
