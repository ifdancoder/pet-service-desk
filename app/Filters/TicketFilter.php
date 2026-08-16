<?php

namespace App\Filters;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

class TicketFilter
{
    /**
     * @param  Builder<Ticket>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Ticket>
     */
    public function apply(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn (Builder $q, $v) => $q->where('priority', $v))
            ->when($filters['assignee_id'] ?? null, fn (Builder $q, $v) => $q->where('assignee_id', $v))
            ->when($filters['requester_id'] ?? null, fn (Builder $q, $v) => $q->where('requester_id', $v))
            ->when($filters['team_id'] ?? null, fn (Builder $q, $v) => $q->where('team_id', $v))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $v) => $q->where('department_id', $v))
            ->when($filters['category_id'] ?? null, fn (Builder $q, $v) => $q->where('category_id', $v))
            ->when($filters['tag'] ?? null, fn (Builder $q, $v) => $q->whereHas(
                'tags', fn (Builder $t) => $t->where('slug', $v)
            ));
    }
}
