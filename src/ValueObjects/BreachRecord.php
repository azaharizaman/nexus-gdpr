<?php

declare(strict_types=1);

namespace Nexus\GDPR\ValueObjects;

use DateTimeImmutable;
use Nexus\GDPR\Enums\BreachSeverity;

final readonly class BreachRecord
{
    /**
     * @param array<int, string> $dataCategories
     * @param array<int, string> $containmentActions
     */
    public function __construct(
        public string $id,
        public string $description,
        public BreachSeverity $severity,
        public DateTimeImmutable $discoveredAt,
        public bool $regulatoryNotified = false,
        public ?DateTimeImmutable $regulatoryNotifiedAt = null,
        public array $dataCategories = [],
        public int $recordsAffected = 0,
        public array $containmentActions = []
    ) {}
}
