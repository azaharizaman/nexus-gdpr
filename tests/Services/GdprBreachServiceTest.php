<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\BreachRecordManagerInterface;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;
use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\GDPR\Services\GdprBreachService;
use Nexus\GDPR\ValueObjects\GdprDeadline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdprBreachService::class)]
final class GdprBreachServiceTest extends TestCase
{
    private BreachRecordManagerInterface&MockObject $breachManager;

    private GdprBreachService $service;

    protected function setUp(): void
    {
        $this->breachManager = $this->createMock(BreachRecordManagerInterface::class);
        $this->service = new GdprBreachService($this->breachManager);
    }

    /**
     * Helper to create BreachRecord with correct constructor.
     */
    private function createBreach(
        string $id,
        BreachSeverity $severity = BreachSeverity::HIGH,
        ?DateTimeImmutable $discoveredAt = null,
        bool $regulatoryNotified = false,
        ?DateTimeImmutable $regulatoryNotifiedAt = null,
        string $description = 'Test breach description with sufficient detail',
        int $recordsAffected = 100,
        array $dataCategories = null,
        ?string $containmentActions = 'Isolated systems',
    ): BreachRecord {
        return new BreachRecord(
            id: $id,
            title: 'Test Breach',
            severity: $severity,
            discoveredAt: $discoveredAt ?? new DateTimeImmutable(),
            occurredAt: ($discoveredAt ?? new DateTimeImmutable())->modify('-1 day'),
            recordsAffected: $recordsAffected,
            dataCategories: $dataCategories ?? [DataCategory::CONTACT],
            description: $description,
            cause: 'Unauthorized access',
            containmentActions: $containmentActions,
            regulatoryNotified: $regulatoryNotified,
            regulatoryNotifiedAt: $regulatoryNotifiedAt,
            individualsNotified: false,
            individualsNotifiedAt: null,
            containedAt: false,
            resolvedAt: null,
            reportedBy: 'security@example.com',
            incidentManager: null,
            metadata: [],
        );
    }

    #[Test]
    public function get_notification_deadline_returns_gdpr_deadline(): void
    {
        $discoveredAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $breach = $this->createBreach('breach-1', discoveredAt: $discoveredAt);

        $deadline = $this->service->getNotificationDeadline($breach);

        $this->assertInstanceOf(GdprDeadline::class, $deadline);
        $this->assertEquals($discoveredAt, $deadline->startDate);
    }

    #[Test]
    public function is_notification_overdue_returns_false_for_low_severity(): void
    {
        $breach = $this->createBreach('breach-1', severity: BreachSeverity::LOW);

        // Low severity doesn't require notification
        $this->assertFalse($this->service->isNotificationOverdue($breach));
    }

    #[Test]
    public function is_notification_overdue_returns_false_if_already_notified(): void
    {
        $discoveredAt = new DateTimeImmutable('-5 days');
        $breach = $this->createBreach(
            'breach-1',
            discoveredAt: $discoveredAt,
            regulatoryNotified: true,
            regulatoryNotifiedAt: new DateTimeImmutable('-2 days')
        );

        $this->assertFalse($this->service->isNotificationOverdue($breach));
    }

    #[Test]
    public function is_notification_overdue_returns_true_after_72_hours(): void
    {
        $discoveredAt = new DateTimeImmutable('-4 days'); // > 72 hours ago
        $breach = $this->createBreach('breach-1', discoveredAt: $discoveredAt);

        $this->assertTrue($this->service->isNotificationOverdue($breach));
    }

    #[Test]
    public function is_notification_overdue_returns_false_within_72_hours(): void
    {
        $discoveredAt = new DateTimeImmutable('-1 day'); // < 72 hours ago
        $breach = $this->createBreach('breach-1', discoveredAt: $discoveredAt);

        $this->assertFalse($this->service->isNotificationOverdue($breach));
    }

    #[Test]
    public function requires_regulatory_notification_returns_false_for_low_severity(): void
    {
        $breach = $this->createBreach('breach-1', severity: BreachSeverity::LOW);

        $this->assertFalse($this->service->requiresRegulatoryNotification($breach));
    }

    #[Test]
    #[DataProvider('severityNotificationRequirementsProvider')]
    public function requires_regulatory_notification_based_on_severity(
        BreachSeverity $severity,
        bool $expected
    ): void {
        $breach = $this->createBreach('breach-1', severity: $severity);

        $this->assertEquals($expected, $this->service->requiresRegulatoryNotification($breach));
    }

    public static function severityNotificationRequirementsProvider(): array
    {
        return [
            'low severity' => [BreachSeverity::LOW, false],
            'medium severity' => [BreachSeverity::MEDIUM, true],
            'high severity' => [BreachSeverity::HIGH, true],
            'critical severity' => [BreachSeverity::CRITICAL, true],
        ];
    }

    #[Test]
    #[DataProvider('severityIndividualNotificationProvider')]
    public function requires_individual_notification_based_on_severity(
        BreachSeverity $severity,
        bool $expected
    ): void {
        $breach = $this->createBreach('breach-1', severity: $severity);

        $this->assertEquals($expected, $this->service->requiresIndividualNotification($breach));
    }

    public static function severityIndividualNotificationProvider(): array
    {
        return [
            'low severity' => [BreachSeverity::LOW, false],
            'medium severity' => [BreachSeverity::MEDIUM, false],
            'high severity' => [BreachSeverity::HIGH, true],
            'critical severity' => [BreachSeverity::CRITICAL, true],
        ];
    }

    #[Test]
    public function get_breaches_requiring_notification_filters_correctly(): void
    {
        $breaches = [
            $this->createBreach('breach-high', severity: BreachSeverity::HIGH),
            $this->createBreach('breach-low', severity: BreachSeverity::LOW),
            $this->createBreach(
                'breach-notified',
                severity: BreachSeverity::HIGH,
                regulatoryNotified: true,
                regulatoryNotifiedAt: new DateTimeImmutable('-1 day')
            ),
            $this->createBreach('breach-medium', severity: BreachSeverity::MEDIUM),
        ];

        $this->breachManager
            ->expects($this->once())
            ->method('getUnresolvedBreaches')
            ->willReturn($breaches);

        $result = $this->service->getBreachesRequiringNotification();

        // Should include high and medium, exclude low and already notified
        $this->assertCount(2, $result);
        $ids = array_map(fn($b) => $b->id, $result);
        $this->assertContains('breach-high', $ids);
        $this->assertContains('breach-medium', $ids);
        $this->assertNotContains('breach-low', $ids);
        $this->assertNotContains('breach-notified', $ids);
    }

    #[Test]
    public function get_overdue_notifications_returns_only_overdue(): void
    {
        $oldBreach = $this->createBreach(
            'breach-old',
            discoveredAt: new DateTimeImmutable('-5 days')
        );
        $recentBreach = $this->createBreach(
            'breach-recent',
            discoveredAt: new DateTimeImmutable('-1 day')
        );

        $this->breachManager
            ->expects($this->once())
            ->method('getUnresolvedBreaches')
            ->willReturn([$oldBreach, $recentBreach]);

        $result = $this->service->getOverdueNotifications();

        // Only old breach should be overdue (> 72 hours)
        $this->assertCount(1, $result);
        $this->assertEquals('breach-old', $result[0]->id);
    }

    #[Test]
    public function validate_breach_documentation_returns_error_for_short_description(): void
    {
        $breach = $this->createBreach('breach-1', description: 'Short');

        $errors = $this->service->validateBreachDocumentation($breach);

        $this->assertNotEmpty($errors);
        $descriptionErrors = array_filter($errors, fn($e) => str_contains($e, 'description') || str_contains($e, 'Description'));
        $this->assertNotEmpty($descriptionErrors);
    }

    #[Test]
    public function validate_breach_documentation_returns_error_for_zero_records(): void
    {
        $breach = $this->createBreach('breach-1', recordsAffected: 0);

        $errors = $this->service->validateBreachDocumentation($breach);

        $this->assertNotEmpty($errors);
        $recordErrors = array_filter($errors, fn($e) => str_contains($e, 'record') || str_contains($e, 'Record'));
        $this->assertNotEmpty($recordErrors);
    }

    #[Test]
    public function validate_breach_documentation_returns_error_for_missing_mitigation(): void
    {
        $breach = $this->createBreach('breach-1', containmentActions: null);

        $errors = $this->service->validateBreachDocumentation($breach);

        $this->assertNotEmpty($errors);
        $containmentErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'containment') || str_contains(strtolower($e), 'mitigation'));
        $this->assertNotEmpty($containmentErrors);
    }

    #[Test]
    public function validate_breach_documentation_returns_error_for_empty_data_types(): void
    {
        $breach = $this->createBreach('breach-1', dataCategories: []);

        $errors = $this->service->validateBreachDocumentation($breach);

        $this->assertNotEmpty($errors);
        $dataTypeErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'data') && (str_contains(strtolower($e), 'categor') || str_contains(strtolower($e), 'type')));
        $this->assertNotEmpty($dataTypeErrors);
    }

    #[Test]
    public function validate_breach_documentation_checks_consequences_for_high_severity(): void
    {
        $breach = $this->createBreach(
            'breach-1',
            severity: BreachSeverity::HIGH,
            description: 'A security breach occurred involving unauthorized access to personal data'
        );

        $errors = $this->service->validateBreachDocumentation($breach);

        // High severity should require consequence documentation
        $consequenceErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'consequence') || str_contains(strtolower($e), 'impact'));
        $this->assertNotEmpty($consequenceErrors);
    }

    #[Test]
    public function validate_breach_documentation_passes_when_consequences_mentioned(): void
    {
        $breach = $this->createBreach(
            'breach-1',
            severity: BreachSeverity::HIGH,
            description: 'A breach occurred with significant impact on data subjects including identity theft risk and financial consequences for affected users'
        );

        $errors = $this->service->validateBreachDocumentation($breach);

        // Should pass consequence check
        $consequenceErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'consequence'));
        $this->assertEmpty($consequenceErrors);
    }

    #[Test]
    public function get_hours_until_notification_deadline_returns_positive_before_deadline(): void
    {
        $discoveredAt = new DateTimeImmutable('-24 hours');
        $breach = $this->createBreach('breach-1', discoveredAt: $discoveredAt);

        $hours = $this->service->getHoursUntilNotificationDeadline($breach);

        // 72 - 24 = 48 hours remaining
        $this->assertEqualsWithDelta(48, $hours, 1);
    }

    #[Test]
    public function get_hours_until_notification_deadline_returns_negative_after_deadline(): void
    {
        $discoveredAt = new DateTimeImmutable('-96 hours');
        $breach = $this->createBreach('breach-1', discoveredAt: $discoveredAt);

        $hours = $this->service->getHoursUntilNotificationDeadline($breach);

        // 72 - 96 = -24 hours (overdue by 24 hours)
        $this->assertEqualsWithDelta(-24, $hours, 1);
    }

    #[Test]
    public function get_notification_compliance_summary_returns_correct_structure(): void
    {
        $this->breachManager
            ->method('getUnresolvedBreaches')
            ->willReturn([]);

        $this->breachManager
            ->method('getAllBreaches')
            ->willReturn([]);

        $summary = $this->service->getNotificationComplianceSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_requiring_notification', $summary);
        $this->assertArrayHasKey('overdue', $summary);
        $this->assertArrayHasKey('urgent_within_24h', $summary);
        $this->assertArrayHasKey('within_deadline', $summary);
        $this->assertArrayHasKey('compliance_rate', $summary);
    }

    #[Test]
    public function get_notification_compliance_summary_calculates_compliance_rate(): void
    {
        // 2 breaches requiring notification: 1 notified, 1 not
        $notifiedBreach = $this->createBreach(
            'breach-notified',
            severity: BreachSeverity::HIGH,
            regulatoryNotified: true,
            regulatoryNotifiedAt: new DateTimeImmutable()
        );
        $pendingBreach = $this->createBreach(
            'breach-pending',
            severity: BreachSeverity::HIGH,
            regulatoryNotified: false
        );
        $lowBreach = $this->createBreach(
            'breach-low',
            severity: BreachSeverity::LOW
        );

        $this->breachManager
            ->method('getUnresolvedBreaches')
            ->willReturn([$pendingBreach, $lowBreach]);

        $this->breachManager
            ->method('getAllBreaches')
            ->willReturn([$notifiedBreach, $pendingBreach, $lowBreach]);

        $summary = $this->service->getNotificationComplianceSummary();

        // Only pending breach requiring notification (high), not low and not already-notified
        $this->assertEquals(1, $summary['total_requiring_notification']);
        // 0 overdue since pending breach was created "now"
        $this->assertEquals(0, $summary['overdue']);
        // Compliance rate = (1 - 0) / 1 = 100%
        $this->assertEquals(100.0, $summary['compliance_rate']);
    }
}
