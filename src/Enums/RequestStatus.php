<?php

declare(strict_types=1);

namespace Nexus\GDPR\Enums;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';
}
