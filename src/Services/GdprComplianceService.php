<?php

declare(strict_types=1);

namespace Nexus\GDPR\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\GDPR\Contracts\GdprComplianceServiceInterface;
use Nexus\GDPR\Exceptions\GdprException;
use Nexus\GDPR\ValueObjects\GdprDeadline;

/**
 * Service for GDPR compliance monitoring and deadline management.
 *
 * Implements GDPR Article 12 requirements:
 * - 30-day deadline for responding to data subject requests
 * - Extension of up to 2 additional months for complex requests
 * - Notification requirements when extensions are applied
 */
final readonly class GdprComplianceService implements GdprComplianceServiceInterface
{
    public function __construct(
        private DataSubjectRequestManagerInterface $requestManager
    ) {}

    /**
     * {@inheritDoc}
     */
    public function calculateDeadline(DataSubjectRequest $request): GdprDeadline
    {
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Check if deadline was previously extended (stored in metadata)
        if (isset($request->metadata['deadline_extended']) && $request->metadata['deadline_extended'] === true) {
            $reason = $request->metadata['extension_reason'] ?? 'Previously extended';
            $deadline = $deadline->extend($reason);
        }

        return $deadline;
    }

    /**
     * {@inheritDoc}
     */
    public function canExtendDeadline(DataSubjectRequest $request): bool
    {
        // Can only extend if not already extended
        $deadline = $this->calculateDeadline($request);

        return $deadline->canExtend();
    }

    /**
     * {@inheritDoc}
     */
    public function extendDeadline(DataSubjectRequest $request, string $reason): GdprDeadline
    {
        if (!$this->canExtendDeadline($request)) {
            throw GdprException::extensionLimitExceeded($request->id);
        }

        $currentDeadline = $this->calculateDeadline($request);

        return $currentDeadline->extend($reason);
    }

    /**
     * {@inheritDoc}
     */
    public function isOverdue(DataSubjectRequest $request, ?DateTimeImmutable $asOf = null): bool
    {
        $deadline = $this->calculateDeadline($request);
        $checkDate = $asOf ?? new DateTimeImmutable();

        // Only check overdue for pending/in-progress requests
        if ($request->status === RequestStatus::COMPLETED || $request->status === RequestStatus::REJECTED) {
            return false;
        }

        return $deadline->isOverdue($checkDate);
    }

    /**
     * {@inheritDoc}
     */
    public function getOverdueRequests(): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $now = new DateTimeImmutable();
        $overdue = [];

        foreach ($activeRequests as $request) {
            if ($this->isOverdue($request, $now)) {
                $overdue[] = $request;
            }
        }

        return $overdue;
    }

    /**
     * {@inheritDoc}
     */
    public function validateGdprCompliance(DataSubjectRequest $request): array
    {
        $errors = [];

        // Check if deadline is exceeded
        if ($this->isOverdue($request)) {
            $deadline = $this->calculateDeadline($request);
            $daysOverdue = $deadline->getDaysOverdue(new DateTimeImmutable());
            $errors[] = "Request is {$daysOverdue} days overdue";
        }

        // Check if request has been acknowledged within 7 days (best practice)
        if ($request->status === RequestStatus::PENDING) {
            $daysSinceSubmission = $request->submittedAt->diff(new DateTimeImmutable())->days;
            if ($daysSinceSubmission > 7) {
                $errors[] = 'Request should be acknowledged within 7 days';
            }
        }

        // Check if identity has been verified for access/portability requests
        // This is done via metadata since it's regulation-specific
        if (
            $request->type->value === 'access' || $request->type->value === 'portability'
        ) {
            $identityVerified = $request->metadata['identity_verified'] ?? false;
            if (!$identityVerified) {
                $errors[] = 'Identity must be verified before processing access/portability requests';
            }
        }

        return $errors;
    }

    /**
     * Get requests approaching deadline (within 7 days).
     *
     * @return array<DataSubjectRequest>
     */
    public function getRequestsApproachingDeadline(int $withinDays = 7): array
    {
        return $this->getRequestsDueWithinDays($withinDays);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestsDueWithinDays(int $days): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $now = new DateTimeImmutable();
        $approaching = [];

        foreach ($activeRequests as $request) {
            $deadline = $this->calculateDeadline($request);
            $daysRemaining = $deadline->getDaysRemaining($now);

            if ($daysRemaining >= 0 && $daysRemaining <= $days) {
                $approaching[] = $request;
            }
        }

        return $approaching;
    }

    /**
     * Get compliance summary for reporting.
     *
     * @return array<string, mixed>
     */
    public function getComplianceSummary(): array
    {
        $activeRequests = $this->requestManager->getActiveRequests();
        $now = new DateTimeImmutable();

        $overdue = 0;
        $approaching = 0;
        $onTrack = 0;
        $totalDaysRemaining = 0;

        foreach ($activeRequests as $request) {
            $deadline = $this->calculateDeadline($request);
            $daysRemaining = $deadline->getDaysRemaining($now);

            if ($daysRemaining < 0) {
                $overdue++;
            } elseif ($daysRemaining <= 7) {
                $approaching++;
            } else {
                $onTrack++;
            }

            $totalDaysRemaining += max(0, $daysRemaining);
        }

        $totalActive = count($activeRequests);

        return [
            'total_pending' => $totalActive,
            'overdue' => $overdue,
            'approaching_deadline' => $approaching,
            'on_track' => $onTrack,
            'average_days_remaining' => $totalActive > 0 ? round($totalDaysRemaining / $totalActive, 1) : 0,
            'compliance_rate' => $totalActive > 0 ? round((($totalActive - $overdue) / $totalActive) * 100, 1) : 100.0,
        ];
    }
}
