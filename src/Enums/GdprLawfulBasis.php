<?php

declare(strict_types=1);

namespace Nexus\GDPR\Enums;

/**
 * GDPR Article 6(1) lawful bases for processing personal data.
 */
enum GdprLawfulBasis: string
{
    /**
     * The data subject has given consent (Article 6(1)(a)).
     */
    case CONSENT = 'consent';

    /**
     * Processing is necessary for contract performance (Article 6(1)(b)).
     */
    case CONTRACT = 'contract';

    /**
     * Processing is necessary for legal obligation (Article 6(1)(c)).
     */
    case LEGAL_OBLIGATION = 'legal_obligation';

    /**
     * Processing is necessary to protect vital interests (Article 6(1)(d)).
     */
    case VITAL_INTERESTS = 'vital_interests';

    /**
     * Processing is necessary for public interest (Article 6(1)(e)).
     */
    case PUBLIC_INTEREST = 'public_interest';

    /**
     * Processing is necessary for legitimate interests (Article 6(1)(f)).
     */
    case LEGITIMATE_INTERESTS = 'legitimate_interests';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CONSENT => 'Consent',
            self::CONTRACT => 'Contract',
            self::LEGAL_OBLIGATION => 'Legal Obligation',
            self::VITAL_INTERESTS => 'Vital Interests',
            self::PUBLIC_INTEREST => 'Public Interest',
            self::LEGITIMATE_INTERESTS => 'Legitimate Interests',
        };
    }

    /**
     * Get detailed description.
     */
    public function description(): string
    {
        return match ($this) {
            self::CONSENT => 'Data subject has given explicit consent for processing',
            self::CONTRACT => 'Processing is necessary for contract performance',
            self::LEGAL_OBLIGATION => 'Processing is required by law',
            self::VITAL_INTERESTS => 'Processing protects vital interests of data subject or another person',
            self::PUBLIC_INTEREST => 'Processing is necessary for public interest or official authority',
            self::LEGITIMATE_INTERESTS => 'Processing is necessary for legitimate interests pursued by controller',
        };
    }

    /**
     * Get GDPR article reference.
     */
    public function articleReference(): string
    {
        return match ($this) {
            self::CONSENT => 'Article 6(1)(a)',
            self::CONTRACT => 'Article 6(1)(b)',
            self::LEGAL_OBLIGATION => 'Article 6(1)(c)',
            self::VITAL_INTERESTS => 'Article 6(1)(d)',
            self::PUBLIC_INTEREST => 'Article 6(1)(e)',
            self::LEGITIMATE_INTERESTS => 'Article 6(1)(f)',
        };
    }

    /**
     * Check if this basis requires a consent mechanism.
     */
    public function requiresConsentMechanism(): bool
    {
        return $this === self::CONSENT;
    }

    /**
     * Check if this basis requires a Legitimate Interest Assessment (LIA).
     */
    public function requiresLegitimateInterestAssessment(): bool
    {
        return $this === self::LEGITIMATE_INTERESTS;
    }

    /**
     * Check if this basis is valid for marketing purposes.
     */
    public function isValidForMarketing(): bool
    {
        return match ($this) {
            self::CONSENT, self::LEGITIMATE_INTERESTS => true,
            default => false,
        };
    }

    /**
     * Check if this basis allows data subject to object.
     */
    public function allowsObjection(): bool
    {
        return match ($this) {
            self::PUBLIC_INTEREST, self::LEGITIMATE_INTERESTS => true,
            default => false,
        };
    }

    /**
     * Check if this basis requires explicit consent (for special categories).
     */
    public function requiresExplicitConsent(): bool
    {
        return $this === self::CONSENT;
    }

    /**
     * Check if this basis supports automated decision-making.
     */
    public function supportsAutomatedDecisions(): bool
    {
        return match ($this) {
            self::CONSENT, self::CONTRACT => true,
            default => false,
        };
    }
}
