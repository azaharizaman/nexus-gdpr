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
    ) {
        if (trim($this->id) === '') {
            throw new \InvalidArgumentException('BreachRecord ID cannot be empty or whitespace only');
        }

        if (trim($this->description) === '') {
            throw new \InvalidArgumentException('BreachRecord description cannot be empty or whitespace only');
        }

        if ($this->regulatoryNotified && $this->regulatoryNotifiedAt === null) {
            throw new \InvalidArgumentException('regulatoryNotifiedAt must be set if regulatoryNotified is true');
        }

        if (!$this->regulatoryNotified && $this->regulatoryNotifiedAt !== null) {
            throw new \InvalidArgumentException('regulatoryNotifiedAt must be null if regulatoryNotified is false');
        }

        if ($this->recordsAffected < 0) {
            throw new \InvalidArgumentException('recordsAffected must be greater than or equal to 0');
        }
    }
}
