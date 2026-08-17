<?php

namespace App\Models;

use Database\Factories\SlaViolationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'sla_policy_id', 'breached_at'])]
class SlaViolation extends Model
{
    /** @use HasFactory<SlaViolationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'breached_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }
}
