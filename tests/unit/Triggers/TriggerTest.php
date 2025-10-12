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
        self::assertNull(Trigger::tryFromArray([]));
    }

    /** @param array<string, string> $trigger */
    #[DataProvider('arrayWithValidValuesProvider')]
    public function testTryFromArrayWithValidValues(array $trigger): void
    {
        $actual = Trigger::tryFromArray($trigger);
        self::assertNotNull($actual);
        self::assertSame('trigger_name', $actual->name->getName());
        self::assertSame(Timing::Before, $actual->timing);
        self::assertSame(Event::Update, $actual->event);
        self::assertSame('test_table', $actual->table->getName());
        self::assertSame('BEGIN END', $actual->statement);
        self::assertSame('definer@localhost', $actual->definer);
    }

    /** @return iterable<array-key, array{array<string, string>}> */
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

    /** @param array<string, null> $trigger */
    #[DataProvider('arrayWithInvalidValuesProvider')]
    public function testTryFromArrayWithInvalidValues(array $trigger): void
    {
        self::assertNull(Trigger::tryFromArray($trigger));
    }

    /** @return iterable<array-key, array{array<string, null>}> */
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

    public function testGetSqlForDropAndCreate(): void
    {
        $testTrigger = Trigger::tryFromArray([
            'Trigger' => 'a_trigger',
            'Timing' => 'BEFORE',
            'Event' => 'UPDATE',
            'Table' => 'test_table2',
            'Statement' => 'BEGIN END',
            'Definer' => 'definer@localhost',
        ]);

        self::assertNotNull($testTrigger);

        self::assertSame(
            'DROP TRIGGER IF EXISTS `a_trigger`',
            $testTrigger->getDropSql(),
        );

        self::assertSame(
            "CREATE TRIGGER `a_trigger` BEFORE UPDATE ON `test_table2`\n FOR EACH ROW BEGIN END\n//\n",
            $testTrigger->getCreateSql(),
        );

        self::assertSame(
            "CREATE TRIGGER `a_trigger` BEFORE UPDATE ON `test_table2`\n FOR EACH ROW BEGIN END\n$$\n",
            $testTrigger->getCreateSql('$$'),
        );
    }
}
