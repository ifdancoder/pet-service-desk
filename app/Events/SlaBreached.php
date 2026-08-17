<?php

namespace App\Events;

use App\Models\SlaViolation;

final class SlaBreached
{
    public function __construct(public readonly SlaViolation $violation) {}
}
