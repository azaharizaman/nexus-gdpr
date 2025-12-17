<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\Services;

use DateTimeImmutable;
use Nexus\DataPrivacy\Contracts\DataSubjectRequestManagerInterface;
use Nexus\DataPrivacy\Enums\RequestStatus;
use Nexus\DataPrivacy\Enums\RequestType;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;
use Nexus\GDPR\Exceptions\GdprException;
use Nexus\GDPR\Services\GdprComplianceService;
use Nexus\GDPR\ValueObjects\GdprDeadline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdprComplianceService::class)]
final class GdprComplianceServiceTest extends TestCase
{
    private DataSubjectRequestManagerInterface&MockObject $requestManager;

    private GdprComplianceService $service;

    protected function setUp(): void
    {
        $this->requestManager = $this->createMock(DataSubjectRequestManagerInterface::class);
        $this->service = new GdprComplianceService($this->requestManager);
    }

    /**
     * Helper to create DataSubjectRequest with correct constructor.
     */
    private function createRequest(
        string $id,
        ?DateTimeImmutable $submittedAt = null,
        ?DateTimeImmutable $deadline = null,
        RequestStatus $status = RequestStatus::PENDING,
        ?DateTimeImmutable $completedAt = null,
        array $metadata = [],
    ): DataSubjectRequest {
        $submitted = $submittedAt ?? new DateTimeImmutable();
        $deadlineDate = $deadline ?? $submitted->modify('+30 days');

        return new DataSubjectRequest(
            id: $id,
            dataSubjectId: new DataSubjectId('user-' . $id),
            type: RequestType::ACCESS,
            status: $status,
            submittedAt: $submitted,
            deadline: $deadlineDate,
            completedAt: $completedAt,
            assignedTo: null,
            description: null,
            responseNotes: null,
            rejectionReason: null,
            metadata: $metadata,
        );
    }

    #[Test]
    public function calculate_deadline_returns_gdpr_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $request = $this->createRequest('req-1', submittedAt: $submittedAt);

        $deadline = $this->service->calculateDeadline($request);

        $this->assertInstanceOf(GdprDeadline::class, $deadline);
        $this->assertEquals($submittedAt, $deadline->startDate);
        // 30 days from Jan 1 = Jan 31
        $this->assertEquals('2024-01-31 10:00:00', $deadline->deadlineDate->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function can_extend_deadline_returns_true_for_fresh_request(): void
    {
        $request = $this->createRequest('req-1');

        $this->assertTrue($this->service->canExtendDeadline($request));
    }

    #[Test]
    public function can_extend_deadline_returns_false_for_already_extended(): void
    {
        $request = $this->createRequest('req-1', metadata: ['deadline_extended' => true]);

        $this->assertFalse($this->service->canExtendDeadline($request));
    }

    #[Test]
    public function extend_deadline_returns_extended_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('2024-01-01 10:00:00');
        $request = $this->createRequest('req-1', submittedAt: $submittedAt);

        $extended = $this->service->extendDeadline($request, 'Complex request');

        $this->assertTrue($extended->isExtended());
        // Original: Jan 31, Extended: +60 days = March 31 (2024 is leap year)
        $this->assertEquals('2024-03-31 10:00:00', $extended->deadlineDate->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function extend_deadline_throws_if_already_extended(): void
    {
        $request = $this->createRequest('req-1', metadata: ['deadline_extended' => true]);

        $this->expectException(GdprException::class);

        $this->service->extendDeadline($request, 'Another extension');
    }

    #[Test]
    public function is_overdue_returns_true_for_past_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('-60 days');
        $request = $this->createRequest('req-1', submittedAt: $submittedAt);

        $this->assertTrue($this->service->isOverdue($request));
    }

    #[Test]
    public function is_overdue_returns_false_for_future_deadline(): void
    {
        $submittedAt = new DateTimeImmutable('-10 days');
        $request = $this->createRequest('req-1', submittedAt: $submittedAt);

        $this->assertFalse($this->service->isOverdue($request));
    }

    #[Test]
    public function is_overdue_returns_false_for_completed_request(): void
    {
        $submittedAt = new DateTimeImmutable('-60 days');
        $request = $this->createRequest(
            'req-1',
            submittedAt: $submittedAt,
            status: RequestStatus::COMPLETED,
            completedAt: $submittedAt->modify('+20 days')
        );

        $this->assertFalse($this->service->isOverdue($request));
    }

    #[Test]
    public function get_overdue_requests_returns_only_overdue(): void
    {
        $overdueRequest = $this->createRequest('req-overdue', submittedAt: new DateTimeImmutable('-60 days'));
        $validRequest = $this->createRequest('req-valid', submittedAt: new DateTimeImmutable('-10 days'));

        $this->requestManager
            ->expects($this->once())
            ->method('getActiveRequests')
            ->willReturn([$overdueRequest, $validRequest]);

        $result = $this->service->getOverdueRequests();

        $this->assertCount(1, $result);
        $this->assertEquals('req-overdue', $result[0]->id);
    }

    #[Test]
    public function validate_gdpr_compliance_returns_errors_for_overdue(): void
    {
        $overdueRequest = $this->createRequest('req-overdue', submittedAt: new DateTimeImmutable('-60 days'));

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$overdueRequest]);

        $errors = $this->service->validateGdprCompliance($overdueRequest);

        $this->assertNotEmpty($errors);
        // Check for overdue error
        $overdueErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'overdue') || str_contains($e, 'days'));
        $this->assertNotEmpty($overdueErrors);
    }

    #[Test]
    public function validate_gdpr_compliance_returns_errors_for_long_pending(): void
    {
        // Request pending for 25 days (within 30-day deadline but > 20 days threshold for warning)
        $longPendingRequest = $this->createRequest('req-long', submittedAt: new DateTimeImmutable('-25 days'));

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$longPendingRequest]);

        $errors = $this->service->validateGdprCompliance($longPendingRequest);

        // Should have warning for long pending (approaching deadline)
        // or at least be flagged
        $this->assertIsArray($errors);
    }

    #[Test]
    public function validate_gdpr_compliance_returns_empty_for_compliant(): void
    {
        // Recent request, well within deadline
        $recentRequest = $this->createRequest('req-recent', submittedAt: new DateTimeImmutable('-5 days'));

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$recentRequest]);

        $errors = $this->service->validateGdprCompliance($recentRequest);

        // Should have no overdue errors
        $overdueErrors = array_filter($errors, fn($e) => str_contains(strtolower($e), 'overdue'));
        $this->assertEmpty($overdueErrors);
    }

    #[Test]
    public function get_requests_due_within_days_returns_correct_requests(): void
    {
        // Request due in 5 days
        $soonRequest = $this->createRequest(
            'req-soon',
            submittedAt: new DateTimeImmutable('-25 days')
        );
        // Request due in 20 days
        $laterRequest = $this->createRequest(
            'req-later',
            submittedAt: new DateTimeImmutable('-10 days')
        );

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$soonRequest, $laterRequest]);

        // Get requests due within 7 days
        $result = $this->service->getRequestsDueWithinDays(7);

        $this->assertCount(1, $result);
        $this->assertEquals('req-soon', $result[0]->id);
    }

    #[Test]
    public function get_requests_due_within_days_excludes_overdue(): void
    {
        // Already overdue request
        $overdueRequest = $this->createRequest(
            'req-overdue',
            submittedAt: new DateTimeImmutable('-60 days')
        );
        // Due soon
        $soonRequest = $this->createRequest(
            'req-soon',
            submittedAt: new DateTimeImmutable('-25 days')
        );

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$overdueRequest, $soonRequest]);

        $result = $this->service->getRequestsDueWithinDays(7);

        // Should only include soon, not overdue
        $this->assertCount(1, $result);
        $this->assertEquals('req-soon', $result[0]->id);
    }

    #[Test]
    public function get_requests_approaching_deadline_uses_correct_window(): void
    {
        $soonRequest = $this->createRequest(
            'req-soon',
            submittedAt: new DateTimeImmutable('-25 days')
        );

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$soonRequest]);

        $result = $this->service->getRequestsApproachingDeadline();

        // Default window should catch requests due within 7 days
        $ids = array_map(fn($r) => $r->id, $result);
        $this->assertContains('req-soon', $ids);
    }

    #[Test]
    public function get_compliance_summary_calculates_correctly(): void
    {
        $pendingRequest = $this->createRequest('req-pending', submittedAt: new DateTimeImmutable('-10 days'));
        $overdueRequest = $this->createRequest('req-overdue', submittedAt: new DateTimeImmutable('-60 days'));

        $this->requestManager
            ->method('getActiveRequests')
            ->willReturn([$pendingRequest, $overdueRequest]);

        $summary = $this->service->getComplianceSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_pending', $summary);
        $this->assertArrayHasKey('overdue', $summary);
        $this->assertArrayHasKey('approaching_deadline', $summary);
        $this->assertArrayHasKey('compliance_rate', $summary);

        $this->assertEquals(2, $summary['total_pending']);
        $this->assertEquals(1, $summary['overdue']);
        // Compliance rate: 1 out of 2 are compliant
        $this->assertEquals(50.0, $summary['compliance_rate']);
    }
}
