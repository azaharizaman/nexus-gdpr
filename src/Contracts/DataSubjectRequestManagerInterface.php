<?php

declare(strict_types=1);

namespace Nexus\GDPR\Contracts;

use Nexus\GDPR\ValueObjects\DataSubjectRequest;

interface DataSubjectRequestManagerInterface
{
    /** @return array<int, DataSubjectRequest> */
    public function getActiveRequests(): array;
}
