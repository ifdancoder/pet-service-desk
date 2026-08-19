<?php

namespace App\Models\Concerns;

use App\Observers\AuditObserver;

trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::whenBooted(fn () => static::observe(AuditObserver::class));
    }
}
