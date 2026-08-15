<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Database\Factories\TicketCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'active', 'default_priority'])]
class TicketCategory extends Model
{
    /** @use HasFactory<TicketCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'default_priority' => TicketPriority::class,
        ];
    }
}
