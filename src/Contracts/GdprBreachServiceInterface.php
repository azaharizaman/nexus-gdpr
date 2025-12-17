<?php

declare(strict_types=1);

namespace Nexus\GDPR\Contracts;

use DateTimeImmutable;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\GDPR\ValueObjects\GdprDeadline;

/**
 * Service interface for GDPR breach notification requirements.
 */
interface GdprBreachServiceInterface
{
    /**
     * Get the 72-hour notification deadline for a breach.
     */
    public function getNotificationDeadline(BreachRecord $breach): GdprDeadline;

    /**
     * Check if breach notification is overdue.
     */
    public function isNotificationOverdue(BreachRecord $breach, ?DateTimeImmutable $asOf = null): bool;

    /**
     * Check if breach requires regulatory notification.
     */
    public function requiresRegulatoryNotification(BreachRecord $breach): bool;

    /**
     * Check if breach requires individual notification.
     */
    public function requiresIndividualNotification(BreachRecord $breach): bool;

    /**
     * Get breaches requiring notification.
     *
     * @return array<BreachRecord>
     */
    public function getBreachesRequiringNotification(): array;

    /**
     * Get overdue breach notifications.
     *
     * @return array<BreachRecord>
     */
    public function getOverdueNotifications(): array;

    /**
     * Validate breach documentation meets GDPR requirements.
     *
     * @return array<string> Validation errors
     */
    public function validateBreachDocumentation(BreachRecord $breach): array;
}
