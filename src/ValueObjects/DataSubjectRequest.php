<?php

declare(strict_types=1);

namespace Nexus\GDPR\ValueObjects;

use DateTimeImmutable;
use Nexus\GDPR\Enums\DataSubjectRequestType;
use Nexus\GDPR\Enums\RequestStatus;

final readonly class DataSubjectRequest
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public DataSubjectRequestType $type,
        public RequestStatus $status,
        public DateTimeImmutable $submittedAt,
        public array $metadata = []
    ) {
        if (trim($this->id) === '') {
            throw new \InvalidArgumentException('DataSubjectRequest ID cannot be empty or whitespace only');
        }

        foreach (array_keys($this->metadata) as $key) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('All metadata keys must be strings');
            }
        }
    }
}
