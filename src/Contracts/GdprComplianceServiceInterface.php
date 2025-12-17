<?php

declare(strict_types=1);

namespace Nexus\GDPR\Contracts;

use DateTimeImmutable;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\GDPR\ValueObjects\GdprDeadline;

/**
 * Service interface for GDPR compliance operations.
 */
interface GdprComplianceServiceInterface
{
    /**
     * Calculate GDPR-compliant deadline for a request.
     */
    public function calculateDeadline(DataSubjectRequest $request): GdprDeadline;

    /**
     * Check if a request deadline can be extended.
     */
    public function canExtendDeadline(DataSubjectRequest $request): bool;

    /**
     * Extend a request deadline with reason.
     *
     * @throws \InvalidArgumentException If extension not allowed
     */
    public function extendDeadline(DataSubjectRequest $request, string $reason): GdprDeadline;

    /**
     * Check if a request is overdue.
     */
    public function isOverdue(DataSubjectRequest $request, ?DateTimeImmutable $asOf = null): bool;

    /**
     * Get all overdue requests.
     *
     * @return array<DataSubjectRequest>
     */
    public function getOverdueRequests(): array;

    /**
     * Get requests due within specified days.
     *
     * @return array<DataSubjectRequest>
     */
    public function getRequestsDueWithinDays(int $days): array;

    /**
     * Validate request meets GDPR requirements.
     *
     * @return array<string> Validation errors
     */
    public function validateGdprCompliance(DataSubjectRequest $request): array;
}
