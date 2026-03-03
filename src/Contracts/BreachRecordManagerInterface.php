<?php

declare(strict_types=1);

namespace Nexus\GDPR\Contracts;

use Nexus\GDPR\ValueObjects\BreachRecord;

interface BreachRecordManagerInterface
{
    /** @return array<int, BreachRecord> */
    public function getUnresolvedBreaches(): array;
}
