<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Triggers;

use PhpMyAdmin\Triggers\Event;
use PhpMyAdmin\Triggers\Timing;
use PhpMyAdmin\Triggers\Trigger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Trigger::class)]
class TriggerTest extends TestCase
{
    public function testTryFromArrayWithEmptyArray(): void
    {
        $this->assertNull(Trigger::tryFromArray([]));
    }

    /** @param mixed[] $trigger */
    #[DataProvider('arrayWithValidValuesProvider')]
    public function testTryFromArrayWithValidValues(array $trigger): void
    {
        $actual = Trigger::tryFromArray($trigger);
        $this->assertNotNull($actual);
        $this->assertSame('trigger_name', $actual->name->getName());
        $this->assertSame(Timing::Before, $actual->timing);
        $this->assertSame(Event::Update, $actual->event);
        $this->assertSame('test_table', $actual->table->getName());
        $this->assertSame('BEGIN END', $actual->statement);
        $this->assertSame('definer@localhost', $actual->definer);
    }

    /** @return iterable<array-key, array{mixed[]}> */
    public static function arrayWithValidValuesProvider(): iterable
    {
        yield [
            [
                'Trigger' => 'trigger_name',
                'Timing' => 'BEFORE',
                'Event' => 'UPDATE',
                'Table' => 'test_table',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer@localhost',
            ],
        ];

        yield [
            [
                'TRIGGER_NAME' => 'trigger_name',
                'ACTION_TIMING' => 'BEFORE',
                'EVENT_MANIPULATION' => 'UPDATE',
                'EVENT_OBJECT_TABLE' => 'test_table',
                'ACTION_STATEMENT' => 'BEGIN END',
                'DEFINER' => 'definer@localhost',
            ],
        ];
    }

    /** @param mixed[] $trigger */
    #[DataProvider('arrayWithInvalidValuesProvider')]
    public function testTryFromArrayWithInvalidValues(array $trigger): void
    {
        $this->assertNull(Trigger::tryFromArray($trigger));
    }

    /** @return iterable<array-key, array{mixed[]}> */
    public static function arrayWithInvalidValuesProvider(): iterable
    {
        yield [
            [
                'Trigger' => null,
                'Timing' => null,
                'Event' => null,
                'Table' => null,
                'Statement' => null,
                'Definer' => null,
            ],
        ];

        yield [
            [
                'TRIGGER_NAME' => null,
                'ACTION_TIMING' => null,
                'EVENT_MANIPULATION' => null,
                'EVENT_OBJECT_TABLE' => null,
                'ACTION_STATEMENT' => null,
                'DEFINER' => null,
            ],
        ];
    }
}
