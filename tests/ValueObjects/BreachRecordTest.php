<?php

declare(strict_types=1);

namespace Nexus\GDPR\Tests\ValueObjects;

use DateTimeImmutable;
use Nexus\GDPR\Enums\BreachSeverity;
use Nexus\GDPR\ValueObjects\BreachRecord;
use PHPUnit\Framework\TestCase;

final class BreachRecordTest extends TestCase
{
    public function test_it_throws_exception_for_non_sequential_keys_in_data_categories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('dataCategories must be a sequential list');

        new BreachRecord(
            id: 'breach-1',
            description: 'Test description',
            severity: BreachSeverity::HIGH,
            discoveredAt: new DateTimeImmutable(),
            dataCategories: [1 => 'email', 3 => 'phone']
        );
    }

    public function test_it_throws_exception_for_non_string_elements_in_data_categories(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Every element of dataCategories must be a string');

        new BreachRecord(
            id: 'breach-1',
            description: 'Test description',
            severity: BreachSeverity::HIGH,
            discoveredAt: new DateTimeImmutable(),
            dataCategories: ['email', 123] // @phpstan-ignore-line
        );
    }

    public function test_it_throws_exception_for_non_sequential_keys_in_containment_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('containmentActions must be a sequential list');

        new BreachRecord(
            id: 'breach-1',
            description: 'Test description',
            severity: BreachSeverity::HIGH,
            discoveredAt: new DateTimeImmutable(),
            containmentActions: [5 => 'Isolated server']
        );
    }

    public function test_it_throws_exception_for_non_string_elements_in_containment_actions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Every element of containmentActions must be a string');

        new BreachRecord(
            id: 'breach-1',
            description: 'Test description',
            severity: BreachSeverity::HIGH,
            discoveredAt: new DateTimeImmutable(),
            containmentActions: [new \stdClass()] // @phpstan-ignore-line
        );
    }

    public function test_it_allows_valid_sequential_lists_of_strings(): void
    {
        $record = new BreachRecord(
            id: 'breach-1',
            description: 'Test description',
            severity: BreachSeverity::HIGH,
            discoveredAt: new DateTimeImmutable(),
            dataCategories: ['email', 'phone'],
            containmentActions: ['Isolated server', 'Changed passwords']
        );

        $this->assertEquals(['email', 'phone'], $record->dataCategories);
        $this->assertEquals(['Isolated server', 'Changed passwords'], $record->containmentActions);
    }
}
