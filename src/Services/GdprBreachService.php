<?php

declare(strict_types=1);

namespace Nexus\GDPR\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\GDPR\Contracts\GdprBreachServiceInterface;
use Nexus\GDPR\ValueObjects\GdprDeadline;

/**
 * Service for GDPR breach notification compliance.
 *
 * Implements GDPR Article 33 and 34 requirements:
 * - 72-hour notification to supervisory authority for personal data breaches
 * - Notification to individuals for high-risk breaches
 * - Documentation requirements for all breaches
 */
final readonly class GdprBreachService implements GdprBreachServiceInterface
{
    public function __construct(
        private BreachRecordManagerInterface $breachManager
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getNotificationDeadline(BreachRecord $breach): GdprDeadline
    {
        return GdprDeadline::forBreachNotification($breach);
    }

    /**
     * {@inheritDoc}
     */
    public function isNotificationOverdue(BreachRecord $breach, ?DateTimeImmutable $asOf = null): bool
    {
        // Only check if notification is required
        if (!$this->requiresRegulatoryNotification($breach)) {
            return false;
        }

        // If already notified, not overdue
        if ($breach->regulatoryNotifiedAt !== null) {
            return false;
        }

        $deadline = $this->getNotificationDeadline($breach);
        $checkDate = $asOf ?? new DateTimeImmutable();

        return $deadline->isBreachNotificationOverdue($checkDate);
    }

    /**
     * {@inheritDoc}
     *
     * Per GDPR Article 33, notification is required unless the breach
     * is unlikely to result in a risk to rights and freedoms.
     */
    public function requiresRegulatoryNotification(BreachRecord $breach): bool
    {
        // Low severity breaches may not require notification if
        // risk to rights and freedoms is unlikely
        if ($breach->severity === BreachSeverity::LOW) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * Per GDPR Article 34, individual notification is required when
     * the breach is likely to result in high risk to rights and freedoms.
     */
    public function requiresIndividualNotification(BreachRecord $breach): bool
    {
        // Only high and critical severity require individual notification
        return $breach->severity === BreachSeverity::HIGH
            || $breach->severity === BreachSeverity::CRITICAL;
    }

    /**
     * {@inheritDoc}
     */
    public function getBreachesRequiringNotification(): array
    {
        $breaches = $this->breachManager->getUnresolvedBreaches();
        $requiring = [];

        foreach ($breaches as $breach) {
            // Skip already notified breaches
            if ($breach->regulatoryNotified) {
                continue;
            }
            if ($this->requiresRegulatoryNotification($breach)) {
                $requiring[] = $breach;
            }
        }

        return $requiring;
    }

    /**
     * {@inheritDoc}
     */
    public function getOverdueNotifications(): array
    {
        $requiring = $this->getBreachesRequiringNotification();
        $now = new DateTimeImmutable();
        $overdue = [];

        foreach ($requiring as $breach) {
            if ($this->isNotificationOverdue($breach, $now)) {
                $overdue[] = $breach;
            }
        }

        return $overdue;
    }

    /**
     * {@inheritDoc}
     *
     * GDPR Article 33(3) requires documentation of:
     * - Nature of the breach
     * - Categories and approximate number of data subjects
     * - Categories and approximate number of records
     * - Contact details of DPO or other contact point
     * - Likely consequences of the breach
     * - Measures taken or proposed to address the breach
     */
    public function validateBreachDocumentation(BreachRecord $breach): array
    {
        $errors = [];

        // Nature of breach (description)
        if (strlen($breach->description) < 50) {
            $errors[] = 'Breach description should be comprehensive (at least 50 characters)';
        }

        // Categories of data subjects
        if (empty($breach->dataCategories)) {
            $errors[] = 'Affected data types must be documented';
        }

        // Number of records
        if ($breach->recordsAffected <= 0) {
            $errors[] = 'Approximate number of affected records must be documented';
        }

        // Measures taken
        if (empty($breach->containmentActions)) {
            $errors[] = 'Mitigation measures must be documented';
        }

        // For high severity, additional requirements
        if ($breach->severity === BreachSeverity::HIGH || $breach->severity === BreachSeverity::CRITICAL) {
            // Likely consequences should be in description or notes
            if (!str_contains(strtolower($breach->description), 'consequence') && !str_contains(strtolower($breach->description), 'impact')) {
                $errors[] = 'Likely consequences of the breach should be documented for high-severity breaches';
            }
        }

        return $errors;
    }

    /**
     * Get hours remaining until notification deadline.
     */
    public function getHoursUntilNotificationDeadline(BreachRecord $breach): int
    {
        $deadline = $this->getNotificationDeadline($breach);
        $now = new DateTimeImmutable();

        $diff = $now->diff($deadline->deadlineDate);

        $hours = ($diff->days * 24) + $diff->h;

        return $diff->invert ? -$hours : $hours;
    }

    /**
     * Get breach notification compliance summary.
     *
     * @return array<string, mixed>
     */
    public function getNotificationComplianceSummary(): array
    {
        $requiring = $this->getBreachesRequiringNotification();
        $overdue = $this->getOverdueNotifications();
        $now = new DateTimeImmutable();

        $totalRequiring = count($requiring);
        $totalOverdue = count($overdue);
        $withinDeadline = 0;
        $urgent = 0; // Less than 24 hours remaining

        foreach ($requiring as $breach) {
            $hoursRemaining = $this->getHoursUntilNotificationDeadline($breach);

            if ($hoursRemaining >= 0 && $hoursRemaining <= 24) {
                $urgent++;
            } elseif ($hoursRemaining > 0) {
                $withinDeadline++;
            }
        }

        return [
            'total_requiring_notification' => $totalRequiring,
            'overdue' => $totalOverdue,
            'urgent_within_24h' => $urgent,
            'within_deadline' => $withinDeadline,
            'compliance_rate' => $totalRequiring > 0
                ? round((($totalRequiring - $totalOverdue) / $totalRequiring) * 100, 1)
                : 100.0,
        ];
    }
}
