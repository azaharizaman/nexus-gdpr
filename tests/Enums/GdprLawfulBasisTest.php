<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\Enums;

use Nexus\GDPR\Enums\GdprLawfulBasis;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdprLawfulBasis::class)]
final class GdprLawfulBasisTest extends TestCase
{
    #[Test]
    public function all_lawful_bases_have_labels(): void
    {
        foreach (GdprLawfulBasis::cases() as $basis) {
            $label = $basis->label();
            $this->assertNotEmpty($label);
            $this->assertIsString($label);
        }
    }

    #[Test]
    public function all_lawful_bases_have_descriptions(): void
    {
        foreach (GdprLawfulBasis::cases() as $basis) {
            $description = $basis->description();
            $this->assertNotEmpty($description);
            $this->assertIsString($description);
        }
    }

    #[Test]
    public function consent_requires_consent_mechanism(): void
    {
        $this->assertTrue(GdprLawfulBasis::CONSENT->requiresConsentMechanism());
        $this->assertFalse(GdprLawfulBasis::CONTRACT->requiresConsentMechanism());
        $this->assertFalse(GdprLawfulBasis::LEGAL_OBLIGATION->requiresConsentMechanism());
        $this->assertFalse(GdprLawfulBasis::VITAL_INTERESTS->requiresConsentMechanism());
        $this->assertFalse(GdprLawfulBasis::PUBLIC_INTEREST->requiresConsentMechanism());
        $this->assertFalse(GdprLawfulBasis::LEGITIMATE_INTERESTS->requiresConsentMechanism());
    }

    #[Test]
    public function legitimate_interests_requires_lia(): void
    {
        $this->assertTrue(GdprLawfulBasis::LEGITIMATE_INTERESTS->requiresLegitimateInterestAssessment());
        $this->assertFalse(GdprLawfulBasis::CONSENT->requiresLegitimateInterestAssessment());
        $this->assertFalse(GdprLawfulBasis::CONTRACT->requiresLegitimateInterestAssessment());
    }

    #[Test]
    public function is_valid_for_marketing_returns_correct_values(): void
    {
        $this->assertTrue(GdprLawfulBasis::CONSENT->isValidForMarketing());
        $this->assertTrue(GdprLawfulBasis::LEGITIMATE_INTERESTS->isValidForMarketing());
        $this->assertFalse(GdprLawfulBasis::CONTRACT->isValidForMarketing());
        $this->assertFalse(GdprLawfulBasis::LEGAL_OBLIGATION->isValidForMarketing());
        $this->assertFalse(GdprLawfulBasis::VITAL_INTERESTS->isValidForMarketing());
        $this->assertFalse(GdprLawfulBasis::PUBLIC_INTEREST->isValidForMarketing());
    }

    #[Test]
    #[DataProvider('articleReferenceProvider')]
    public function article_references_are_correct(GdprLawfulBasis $basis, string $expected): void
    {
        $this->assertSame($expected, $basis->articleReference());
    }

    /**
     * @return array<string, array{GdprLawfulBasis, string}>
     */
    public static function articleReferenceProvider(): array
    {
        return [
            'consent' => [GdprLawfulBasis::CONSENT, 'Article 6(1)(a)'],
            'contract' => [GdprLawfulBasis::CONTRACT, 'Article 6(1)(b)'],
            'legal_obligation' => [GdprLawfulBasis::LEGAL_OBLIGATION, 'Article 6(1)(c)'],
            'vital_interests' => [GdprLawfulBasis::VITAL_INTERESTS, 'Article 6(1)(d)'],
            'public_interest' => [GdprLawfulBasis::PUBLIC_INTEREST, 'Article 6(1)(e)'],
            'legitimate_interests' => [GdprLawfulBasis::LEGITIMATE_INTERESTS, 'Article 6(1)(f)'],
        ];
    }

    #[Test]
    public function enum_values_are_snake_case(): void
    {
        $this->assertSame('consent', GdprLawfulBasis::CONSENT->value);
        $this->assertSame('contract', GdprLawfulBasis::CONTRACT->value);
        $this->assertSame('legal_obligation', GdprLawfulBasis::LEGAL_OBLIGATION->value);
        $this->assertSame('vital_interests', GdprLawfulBasis::VITAL_INTERESTS->value);
        $this->assertSame('public_interest', GdprLawfulBasis::PUBLIC_INTEREST->value);
        $this->assertSame('legitimate_interests', GdprLawfulBasis::LEGITIMATE_INTERESTS->value);
    }
}
