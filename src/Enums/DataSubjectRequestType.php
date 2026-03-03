<?php

declare(strict_types=1);

namespace Nexus\GDPR\Enums;

enum DataSubjectRequestType: string
{
    case ACCESS = 'access';
    case PORTABILITY = 'portability';
    case ERASURE = 'erasure';
    case RECTIFICATION = 'rectification';
    case RESTRICTION = 'restriction';
    case OBJECTION = 'objection';
}
