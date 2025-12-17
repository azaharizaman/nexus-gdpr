# Nexus\GDPR

**EU General Data Protection Regulation (GDPR) Compliance Extension**

## Overview

`Nexus\GDPR` extends `Nexus\DataPrivacy` with EU-specific compliance requirements:

- **30-day deadline** for Data Subject Access Requests (DSARs)
- **72-hour breach notification** to supervisory authority
- **Specific lawful basis** requirements (Article 6)
- **Special category data** handling (Article 9)
- **Data transfer** rules for third countries

## Installation

```bash
composer require nexus/gdpr
```

## Key Features

### 1. GDPR Deadline Enforcement

```php
use Nexus\GDPR\Services\GdprComplianceService;
use Nexus\DataPrivacy\ValueObjects\DataSubjectRequest;

$service = new GdprComplianceService($requestManager);

// Get deadline for DSAR (30 calendar days)
$deadline = $service->calculateDeadline($request);

// Check if extension is allowed (up to 2 months for complex requests)
$canExtend = $service->canExtendDeadline($request);
```

### 2. Breach Notification Deadlines

```php
use Nexus\GDPR\Services\GdprBreachService;

$service = new GdprBreachService($breachManager);

// Calculate 72-hour notification deadline
$deadline = $service->getNotificationDeadline($breach);

// Check if notification is overdue
$isOverdue = $service->isNotificationOverdue($breach);
```

### 3. Lawful Basis Validation

```php
use Nexus\GDPR\Services\LawfulBasisValidator;
use Nexus\DataPrivacy\Enums\LawfulBasisType;

$validator = new LawfulBasisValidator();

// Validate processing activity has valid lawful basis
$isValid = $validator->validate($processingActivity);

// Check if consent is required for purpose
$needsConsent = $validator->requiresConsent(LawfulBasisType::CONSENT);
```

## GDPR-Specific Deadlines

| Requirement | Deadline | Extension |
|-------------|----------|-----------|
| DSAR Response | 30 days | +2 months (complex) |
| Breach Notification | 72 hours | None |
| Consent Withdrawal | Immediate | None |
| Data Portability | 30 days | +2 months (complex) |

## Architecture

This package extends `Nexus\DataPrivacy` without modifying it:

```
Nexus\GDPR
├── Services/
│   ├── GdprComplianceService    # Deadline calculations
│   ├── GdprBreachService        # 72-hour notification
│   └── LawfulBasisValidator     # Article 6 validation
├── ValueObjects/
│   └── GdprDeadline             # Deadline with extension rules
└── Enums/
    └── GdprLawfulBasis          # GDPR-specific bases
```

## Usage with DataPrivacy

```php
// Register GDPR-specific handlers
$registry = new RequestHandlerRegistry();
$registry->register(new GdprAccessRequestHandler($gdprService));

// Validate GDPR compliance
$complianceService->validateGdprCompliance($request);
```

## License

MIT License
