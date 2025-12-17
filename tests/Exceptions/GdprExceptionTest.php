<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\Exceptions;

use Nexus\GDPR\Exceptions\GdprException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdprException::class)]
final class GdprExceptionTest extends TestCase
{
    #[Test]
    public function deadline_exceeded_creates_correct_message(): void
    {
        $exception = GdprException::deadlineExceeded('request-123', 5);

        $this->assertStringContainsString('request-123', $exception->getMessage());
        $this->assertStringContainsString('5 days overdue', $exception->getMessage());
    }

    #[Test]
    public function breach_notification_overdue_creates_correct_message(): void
    {
        $exception = GdprException::breachNotificationOverdue('breach-456', 10);

        $this->assertStringContainsString('breach-456', $exception->getMessage());
        $this->assertStringContainsString('10 hours overdue', $exception->getMessage());
        $this->assertStringContainsString('72-hour', $exception->getMessage());
    }

    #[Test]
    public function missing_lawful_basis_creates_correct_message(): void
    {
        $exception = GdprException::missingLawfulBasis('marketing_emails');

        $this->assertStringContainsString('marketing_emails', $exception->getMessage());
        $this->assertStringContainsString('lawful basis', $exception->getMessage());
    }

    #[Test]
    public function invalid_basis_for_special_category_creates_correct_message(): void
    {
        $exception = GdprException::invalidBasisForSpecialCategory('contract', 'health_data');

        $this->assertStringContainsString('contract', $exception->getMessage());
        $this->assertStringContainsString('health_data', $exception->getMessage());
        $this->assertStringContainsString('special category', $exception->getMessage());
    }

    #[Test]
    public function extension_limit_exceeded_creates_correct_message(): void
    {
        $exception = GdprException::extensionLimitExceeded('request-789');

        $this->assertStringContainsString('request-789', $exception->getMessage());
        $this->assertStringContainsString('2 months', $exception->getMessage());
    }
}
