<?php

declare(strict_types=1);

namespace Nexus\GDPR\Exceptions;

use Exception;

/**
 * Base exception for GDPR-related errors.
 */
class GdprException extends Exception
{
    /**
     * Create exception for deadline violation.
     */
    public static function deadlineExceeded(string $requestId, int $daysOverdue): self
    {
        return new self(
            "GDPR deadline exceeded for request {$requestId}: {$daysOverdue} days overdue"
        );
    }

    /**
     * Create exception for breach notification deadline.
     */
    public static function breachNotificationOverdue(string $breachId, int $hoursOverdue): self
    {
        return new self(
            "GDPR 72-hour breach notification deadline exceeded for breach {$breachId}: {$hoursOverdue} hours overdue"
        );
    }

    /**
     * Create exception for missing lawful basis.
     */
    public static function missingLawfulBasis(string $processingActivity): self
    {
        return new self(
            "No lawful basis specified for processing activity: {$processingActivity}"
        );
    }

    /**
     * Create exception for invalid lawful basis for special category data.
     */
    public static function invalidBasisForSpecialCategory(string $basis, string $category): self
    {
        return new self(
            "Lawful basis '{$basis}' is not valid for special category data '{$category}'"
        );
    }

    /**
     * Create exception for extension limit exceeded.
     */
    public static function extensionLimitExceeded(string $requestId): self
    {
        return new self(
            "Maximum extension period (2 months) already applied to request {$requestId}"
        );
    }
}
