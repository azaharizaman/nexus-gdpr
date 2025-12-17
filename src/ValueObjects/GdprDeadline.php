<?php

declare(strict_types=1);

namespace Nexus\GDPR\ValueObjects;

use DateTimeImmutable;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;

/**
 * Represents a GDPR-compliant deadline with extension rules.
 *
 * GDPR Article 12(3) requirements:
 * - Standard deadline: 30 days from receipt of request
 * - Extension: Up to 2 additional months (60 days) for complex requests
 * - Breach notification: 72 hours from awareness (Article 33)
 */
final class GdprDeadline
{
    /**
     * Standard DSAR response deadline in days (Article 12(3)).
     */
    public const STANDARD_DEADLINE_DAYS = 30;

    /**
     * Extension period in days (2 months ≈ 60 days per Article 12(3)).
     */
    public const EXTENSION_DAYS = 60;

    /**
     * Breach notification deadline in hours (Article 33).
     */
    public const BREACH_NOTIFICATION_HOURS = 72;

    private function __construct(
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $deadlineDate,
        private readonly bool $extended = false,
        public readonly ?string $extensionReason = null,
        public readonly ?DateTimeImmutable $originalDeadline = null,
    ) {
    }

    /**
     * Calculate standard GDPR deadline for a data subject request.
     */
    public static function forDataSubjectRequest(DataSubjectRequest $request): self
    {
        $deadlineDate = $request->submittedAt->modify('+' . self::STANDARD_DEADLINE_DAYS . ' days');

        return new self(
            startDate: $request->submittedAt,
            deadlineDate: $deadlineDate,
        );
    }

    /**
     * Calculate breach notification deadline (72 hours from discovery).
     */
    public static function forBreachNotification(BreachRecord $breach): self
    {
        $deadlineDate = $breach->discoveredAt->modify('+' . self::BREACH_NOTIFICATION_HOURS . ' hours');

        return new self(
            startDate: $breach->discoveredAt,
            deadlineDate: $deadlineDate,
        );
    }

    /**
     * Extend deadline by 60 days for complex requests.
     *
     * Per GDPR Article 12(3), extension can only be applied once.
     *
     * @throws \InvalidArgumentException If already extended
     */
    public function extend(string $reason): self
    {
        if ($this->extended) {
            throw new \InvalidArgumentException('Deadline has already been extended');
        }

        $extendedDate = $this->deadlineDate->modify('+' . self::EXTENSION_DAYS . ' days');

        return new self(
            startDate: $this->startDate,
            deadlineDate: $extendedDate,
            extended: true,
            extensionReason: $reason,
            originalDeadline: $this->deadlineDate,
        );
    }

    /**
     * Check if deadline has been extended.
     */
    public function isExtended(): bool
    {
        return $this->extended;
    }

    /**
     * Check if deadline has passed.
     */
    public function isOverdue(?DateTimeImmutable $asOf = null): bool
    {
        $asOf ??= new DateTimeImmutable();

        return $asOf > $this->deadlineDate;
    }

    /**
     * Get remaining days until deadline (negative if overdue).
     */
    public function getDaysRemaining(?DateTimeImmutable $asOf = null): int
    {
        $asOf ??= new DateTimeImmutable();
        $diff = $asOf->diff($this->deadlineDate);

        if ($this->isOverdue($asOf)) {
            return -$diff->days;
        }

        return $diff->days;
    }

    /**
     * Get number of days overdue (0 if not overdue).
     */
    public function getDaysOverdue(?DateTimeImmutable $asOf = null): int
    {
        $asOf ??= new DateTimeImmutable();

        if (!$this->isOverdue($asOf)) {
            return 0;
        }

        return $asOf->diff($this->deadlineDate)->days;
    }

    /**
     * Check if breach notification is overdue (72-hour rule).
     */
    public function isBreachNotificationOverdue(?DateTimeImmutable $asOf = null): bool
    {
        return $this->isOverdue($asOf);
    }

    /**
     * Check if extension is allowed.
     */
    public function canExtend(): bool
    {
        return !$this->extended;
    }

    /**
     * Get progress percentage toward deadline.
     */
    public function getProgressPercentage(?DateTimeImmutable $asOf = null): float
    {
        $asOf ??= new DateTimeImmutable();
        $totalDays = $this->startDate->diff($this->deadlineDate)->days;
        $elapsedDays = $this->startDate->diff($asOf)->days;

        if ($totalDays === 0) {
            return 100.0;
        }

        return min(100.0, ($elapsedDays / $totalDays) * 100);
    }
}
