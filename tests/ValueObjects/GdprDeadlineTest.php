<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\ValueObjects;

use DateTimeImmutable;
use Nexus\GDPR\Enums\BreachSeverity;
use Nexus\GDPR\Enums\DataSubjectRequestType;
use Nexus\GDPR\Enums\RequestStatus;
use Nexus\GDPR\ValueObjects\BreachRecord;
use Nexus\GDPR\ValueObjects\DataSubjectRequest;
use Nexus\GDPR\ValueObjects\GdprDeadline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdprDeadline::class)]
final class GdprDeadlineTest extends TestCase
{
    /**
     * Helper to create DataSubjectRequest with correct constructor.
     */
    private function createRequest(DateTimeImmutable $submittedAt): DataSubjectRequest
    {
        return new DataSubjectRequest(
            id: 'request-' . uniqid(),
            type: DataSubjectRequestType::ACCESS,
            status: RequestStatus::PENDING,
            submittedAt: $submittedAt,
            metadata: [],
        );
    }

    /**
     * Helper to create BreachRecord with correct constructor.
     */
    private function createBreach(DateTimeImmutable $discoveredAt): BreachRecord
    {
        return new BreachRecord(
            id: 'breach-' . uniqid(),
            description: 'Test breach description with sufficient length for validation requirements.',
            severity: BreachSeverity::HIGH,
            discoveredAt: $discoveredAt,
            regulatoryNotified: false,
            regulatoryNotifiedAt: null,
            dataCategories: ['Contact'],
            recordsAffected: 100,
            containmentActions: ['Isolated systems']
        );
    }

    #[Test]
    public function for_data_subject_request_calculates_30_day_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $request = $this->createRequest($submittedAt);

        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // 30 days from Jan 1 = Jan 31
        $this->assertEquals('2024-01-31 10:00:00', $deadline->deadlineDate->format('Y-m-d H:i:s'));
        $this->assertEquals($submittedAt, $deadline->startDate);
        $this->assertFalse($deadline->isExtended());
    }

    #[Test]
    public function for_breach_notification_calculates_72_hour_deadline(): void
    {
        $discoveredAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $breach = $this->createBreach($discoveredAt);

        $deadline = GdprDeadline::forBreachNotification($breach);

        // 72 hours from discovery
        $expected = $discoveredAt->modify('+72 hours');
        $this->assertEquals($expected, $deadline->deadlineDate);
        $this->assertEquals($discoveredAt, $deadline->startDate);
    }

    #[Test]
    public function extend_adds_60_days_to_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        $extended = $deadline->extend('Complex request requiring additional processing time');

        // Original deadline: Jan 31
        // Extended by 60 days: Jan 31 + 60 days = March 31 (2024 is a leap year, Feb has 29 days)
        $this->assertEquals('2024-03-31 10:00:00', $extended->deadlineDate->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-31 10:00:00', $extended->originalDeadline->format('Y-m-d H:i:s'));
        $this->assertTrue($extended->isExtended());
        $this->assertEquals('Complex request requiring additional processing time', $extended->extensionReason);
    }

    #[Test]
    public function extend_throws_if_already_extended(): void
    {
        $request = $this->createRequest(new DateTimeImmutable('2024-01-01'));
        $deadline = GdprDeadline::forDataSubjectRequest($request);
        $extended = $deadline->extend('First extension');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already been extended');

        $extended->extend('Second extension attempt');
    }

    #[Test]
    public function can_extend_returns_true_for_fresh_deadline(): void
    {
        $request = $this->createRequest(new DateTimeImmutable());
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        $this->assertTrue($deadline->canExtend());
    }

    #[Test]
    public function can_extend_returns_false_after_extension(): void
    {
        $request = $this->createRequest(new DateTimeImmutable());
        $deadline = GdprDeadline::forDataSubjectRequest($request);
        $extended = $deadline->extend('Reason');

        $this->assertFalse($extended->canExtend());
    }

    #[Test]
    public function is_overdue_returns_true_after_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Check on Feb 15 (after Jan 31 deadline)
        $checkDate = new DateTimeImmutable('2024-02-15');

        $this->assertTrue($deadline->isOverdue($checkDate));
    }

    #[Test]
    public function is_overdue_returns_false_before_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Check on Jan 15 (before Jan 31 deadline)
        $checkDate = new DateTimeImmutable('2024-01-15');

        $this->assertFalse($deadline->isOverdue($checkDate));
    }

    #[Test]
    public function get_days_remaining_returns_positive_before_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Check on Jan 15 - 16 days remaining until Jan 31
        $checkDate = new DateTimeImmutable('2024-01-15');

        $this->assertEquals(16, $deadline->getDaysRemaining($checkDate));
    }

    #[Test]
    public function get_days_remaining_returns_negative_after_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Check on Feb 10 - 10 days after Jan 31 deadline
        $checkDate = new DateTimeImmutable('2024-02-10');

        $this->assertEquals(-10, $deadline->getDaysRemaining($checkDate));
    }

    #[Test]
    public function get_days_overdue_returns_zero_before_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        $checkDate = new DateTimeImmutable('2024-01-15');

        $this->assertEquals(0, $deadline->getDaysOverdue($checkDate));
    }

    #[Test]
    public function get_days_overdue_returns_correct_days_after_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // 10 days after deadline
        $checkDate = new DateTimeImmutable('2024-02-10');

        $this->assertEquals(10, $deadline->getDaysOverdue($checkDate));
    }

    #[Test]
    public function is_breach_notification_overdue_returns_true_after_72_hours(): void
    {
        $discoveredAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $breach = $this->createBreach($discoveredAt);
        $deadline = GdprDeadline::forBreachNotification($breach);

        // Check 80 hours after discovery (past 72-hour deadline)
        $checkDate = $discoveredAt->modify('+80 hours');

        $this->assertTrue($deadline->isBreachNotificationOverdue($checkDate));
    }

    #[Test]
    public function is_breach_notification_overdue_returns_false_within_72_hours(): void
    {
        $discoveredAt = new DateTimeImmutable('2024-01-15 10:00:00');
        $breach = $this->createBreach($discoveredAt);
        $deadline = GdprDeadline::forBreachNotification($breach);

        // Check 48 hours after discovery (within 72-hour deadline)
        $checkDate = $discoveredAt->modify('+48 hours');

        $this->assertFalse($deadline->isBreachNotificationOverdue($checkDate));
    }

    #[Test]
    public function get_progress_percentage_at_start_is_zero(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        $this->assertEquals(0.0, $deadline->getProgressPercentage($submittedAt));
    }

    #[Test]
    public function get_progress_percentage_at_halfway_is_50(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // 15 days into 30-day period = 50%
        $checkDate = new DateTimeImmutable('2024-01-16');

        $this->assertEquals(50.0, $deadline->getProgressPercentage($checkDate));
    }

    #[Test]
    public function get_progress_percentage_past_deadline_caps_at_100(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01');
        $request = $this->createRequest($submittedAt);
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        // Way past deadline
        $checkDate = new DateTimeImmutable('2024-03-01');

        $this->assertEquals(100.0, $deadline->getProgressPercentage($checkDate));
    }

    #[Test]
    public function original_deadline_is_null_for_non_extended(): void
    {
        $request = $this->createRequest(new DateTimeImmutable());
        $deadline = GdprDeadline::forDataSubjectRequest($request);

        $this->assertNull($deadline->originalDeadline);
    }
}
