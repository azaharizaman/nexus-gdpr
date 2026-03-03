<?php

declare(strict_types=1);

namespace Nexus\GDPR\Enums;

enum BreachSeverity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
