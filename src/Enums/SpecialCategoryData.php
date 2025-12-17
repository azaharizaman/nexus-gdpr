<?php

declare(strict_types=1);

namespace Nexus\GDPR\Enums;

/**
 * Special category data types under GDPR Article 9.
 */
enum SpecialCategoryData: string
{
    case RACIAL_ETHNIC_ORIGIN = 'racial_ethnic_origin';
    case POLITICAL_OPINIONS = 'political_opinions';
    case RELIGIOUS_BELIEFS = 'religious_beliefs';
    case PHILOSOPHICAL_BELIEFS = 'philosophical_beliefs';
    case TRADE_UNION_MEMBERSHIP = 'trade_union_membership';
    case GENETIC_DATA = 'genetic_data';
    case BIOMETRIC_DATA = 'biometric_data';
    case HEALTH_DATA = 'health_data';
    case SEX_LIFE = 'sex_life';
    case SEXUAL_ORIENTATION = 'sexual_orientation';

    /**
     * Check if explicit consent is required.
     */
    public function requiresExplicitConsent(): bool
    {
        return true; // All special categories require explicit consent
    }

    /**
     * Get GDPR article reference.
     */
    public function getArticleReference(): string
    {
        return 'Article 9(1)';
    }

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::RACIAL_ETHNIC_ORIGIN => 'Racial or ethnic origin',
            self::POLITICAL_OPINIONS => 'Political opinions',
            self::RELIGIOUS_BELIEFS => 'Religious beliefs',
            self::PHILOSOPHICAL_BELIEFS => 'Philosophical beliefs',
            self::TRADE_UNION_MEMBERSHIP => 'Trade union membership',
            self::GENETIC_DATA => 'Genetic data',
            self::BIOMETRIC_DATA => 'Biometric data',
            self::HEALTH_DATA => 'Health data',
            self::SEX_LIFE => 'Sex life',
            self::SEXUAL_ORIENTATION => 'Sexual orientation',
        };
    }

    /**
     * Get description of the data category.
     */
    public function description(): string
    {
        return match ($this) {
            self::RACIAL_ETHNIC_ORIGIN => 'Personal data revealing racial or ethnic origin',
            self::POLITICAL_OPINIONS => 'Personal data revealing political opinions',
            self::RELIGIOUS_BELIEFS => 'Personal data revealing religious beliefs',
            self::PHILOSOPHICAL_BELIEFS => 'Personal data revealing philosophical beliefs',
            self::TRADE_UNION_MEMBERSHIP => 'Personal data revealing trade union membership',
            self::GENETIC_DATA => 'Genetic data relating to inherited or acquired genetic characteristics',
            self::BIOMETRIC_DATA => 'Biometric data for uniquely identifying a person',
            self::HEALTH_DATA => 'Data concerning health, physical or mental',
            self::SEX_LIFE => 'Data concerning a person\'s sex life',
            self::SEXUAL_ORIENTATION => 'Data concerning a person\'s sexual orientation',
        };
    }

    /**
     * Get all special category types.
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
