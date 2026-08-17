<?php

namespace App\Services\Sla;

use App\Models\Ticket;
use Carbon\CarbonImmutable;

interface SlaCalculator
{
    public function calculate(Ticket $ticket): ?CarbonImmutable;
}
