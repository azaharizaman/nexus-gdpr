<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\Enums;

use Nexus\GDPR\Enums\SpecialCategoryData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SpecialCategoryData::class)]
final class SpecialCategoryDataTest extends TestCase
{
    #[Test]
    public function all_categories_have_labels(): void
    {
        foreach (SpecialCategoryData::cases() as $category) {
            $label = $category->label();
            $this->assertNotEmpty($label);
            $this->assertIsString($label);
        }
    }

    #[Test]
    public function all_categories_have_descriptions(): void
    {
        foreach (SpecialCategoryData::cases() as $category) {
            $description = $category->description();
            $this->assertNotEmpty($description);
            $this->assertIsString($description);
        }
    }

    #[Test]
    public function enum_has_10_categories(): void
    {
        // GDPR Article 9 lists 10 special categories
        // Note: We have 11 because religious and philosophical beliefs are split
        $this->assertGreaterThanOrEqual(10, count(SpecialCategoryData::cases()));
    }

    #[Test]
    public function health_data_category_exists(): void
    {
        $this->assertSame('health_data', SpecialCategoryData::HEALTH_DATA->value);
    }

    #[Test]
    public function genetic_data_category_exists(): void
    {
        $this->assertSame('genetic_data', SpecialCategoryData::GENETIC_DATA->value);
    }

    #[Test]
    public function biometric_data_category_exists(): void
    {
        $this->assertSame('biometric_data', SpecialCategoryData::BIOMETRIC_DATA->value);
    }

    #[Test]
    public function racial_ethnic_origin_category_exists(): void
    {
        $this->assertSame('racial_ethnic_origin', SpecialCategoryData::RACIAL_ETHNIC_ORIGIN->value);
    }

    #[Test]
    public function political_opinions_category_exists(): void
    {
        $this->assertSame('political_opinions', SpecialCategoryData::POLITICAL_OPINIONS->value);
    }

    #[Test]
    public function religious_beliefs_category_exists(): void
    {
        $this->assertSame('religious_beliefs', SpecialCategoryData::RELIGIOUS_BELIEFS->value);
    }

    #[Test]
    public function trade_union_membership_category_exists(): void
    {
        $this->assertSame('trade_union_membership', SpecialCategoryData::TRADE_UNION_MEMBERSHIP->value);
    }

    #[Test]
    public function sex_life_category_exists(): void
    {
        $this->assertSame('sex_life', SpecialCategoryData::SEX_LIFE->value);
    }

    #[Test]
    public function sexual_orientation_category_exists(): void
    {
        $this->assertSame('sexual_orientation', SpecialCategoryData::SEXUAL_ORIENTATION->value);
    }

    #[Test]
    public function philosophical_beliefs_category_exists(): void
    {
        $this->assertSame('philosophical_beliefs', SpecialCategoryData::PHILOSOPHICAL_BELIEFS->value);
    }

    #[Test]
    public function health_data_requires_explicit_consent(): void
    {
        $this->assertTrue(SpecialCategoryData::HEALTH_DATA->requiresExplicitConsent());
    }

    #[Test]
    public function all_categories_require_explicit_consent(): void
    {
        foreach (SpecialCategoryData::cases() as $category) {
            $this->assertTrue(
                $category->requiresExplicitConsent(),
                "Category {$category->value} should require explicit consent"
            );
        }
    }
}
