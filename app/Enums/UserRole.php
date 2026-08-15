<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case SupportAgent = 'support_agent';
    case TeamLead = 'team_lead';
    case SupportManager = 'support_manager';
    case Administrator = 'administrator';
}
