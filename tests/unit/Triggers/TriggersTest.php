<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Triggers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Triggers\Trigger;
use PhpMyAdmin\Triggers\Triggers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Trigger::class)]
#[CoversClass(Triggers::class)]
class TriggersTest extends AbstractTestCase
{
    private Triggers $triggers;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SELECT 1 FROM `pma_test`.`table1` LIMIT 1;', []);
        $dummyDbi->addResult('SELECT 1 FROM `pma_test`.`table``2` LIMIT 1;', false);
        $dummyDbi->addResult('SELECT 1 FROM `pma_test`.`table3` LIMIT 1;', false);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;
        Config::getInstance()->selectedServer['DisableIS'] = false;
        Current::$database = 'pma_test';
        Current::$table = 'table';

        $dbTableExists = new DbTableExists($dbi);
        $this->triggers = new Triggers($dbi, $dbTableExists);
    }

    /**
     * Test for getQueryFromRequest
     *
     * @param string $definer    Definer
     * @param string $name       Name
     * @param string $timing     Timing
     * @param string $event      Event
     * @param string $table      Table
     * @param string $definition Definition
     * @param string $query      Query
     * @param int    $numErr     Error number
     */
    #[DataProvider('providerGetQueryFromRequest')]
    public function testGetQueryFromRequest(
        string $definer,
        string $name,
        string $timing,
        string $event,
        string $table,
        string $definition,
        string $query,
        int $numErr,
        string $error,
    ): void {
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'item_definer' => $definer,
                'item_name' => $name,
                'item_timing' => $timing,
                'item_event' => $event,
                'item_table' => $table,
                'item_definition' => $definition,
            ]);

        $actual = $this->triggers->getQueryFromRequest($request, DatabaseName::from('pma_test'));
        if ($error !== '') {
            self::assertNotNull(Current::$message);
            self::assertSame($error, Current::$message->getMessage());
        } else {
            self::assertNull(Current::$message);
        }

        self::assertSame($query, $actual);
        self::assertSame($numErr, $this->triggers->getErrorCount());
    }

    /**
     * Data provider for testGetQueryFromRequest
     *
     * @return array<array{string, string, string, string, string, string, string, int, string}>
     */
    public static function providerGetQueryFromRequest(): array
    {
        return [
            [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                5,
                '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
                '<li>You must provide a trigger name!</li> ' .
                '<li>You must provide a valid timing for the trigger!</li> ' .
                '<li>You must provide a valid event for the trigger!</li> ' .
                '<li>You must provide a valid table name!</li> ' .
                '<li>You must provide a trigger definition.</li> ' .
                '</ul>',
            ],
            [
                'root',
                'trigger',
                'BEFORE',
                'INSERT',
                'table`2',
                'SET @A=NULL',
                '',
                2,
                '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
                '<li>The definer must be in the "username@hostname" format!</li> ' .
                '<li>You must provide a valid table name!</li> ' .
                '</ul>',
            ],
            [
                'foo`s@host',
                'trigger`s test',
                'AFTER',
                'foo',
                'table3',
                'BEGIN SET @A=1; SET @B=2; END',
                '',
                2,
                '<b>One or more errors have occurred while processing your request:</b> <ul> ' .
                '<li>You must provide a valid event for the trigger!</li> ' .
                '<li>You must provide a valid table name!</li> ' .
                '</ul>',
            ],
            [
                'root@localhost',
                'trigger',
                'BEFORE',
                'INSERT',
                'table1',
                'SET @A=NULL',
                'CREATE DEFINER=`root`@`localhost` TRIGGER `trigger`'
                    . ' BEFORE INSERT ON `table1` FOR EACH ROW SET @A=NULL',
                0,
                '',
            ],
        ];
    }

    public function testGetDetails(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SHOW TRIGGERS FROM `test_db`',
            [
                ['test_trigger', 'INSERT', 'test_table', 'BEGIN END', 'AFTER', 'definer@localhost'],
                ['a_trigger', 'UPDATE', 'test_table2', 'BEGIN END', 'BEFORE', 'definer2@localhost'],
            ],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer'],
        );

        $triggers = Triggers::getDetails($this->createDatabaseInterface($dbiDummy), 'test_db');
        $expected = [
            Trigger::tryFromArray([
                'Trigger' => 'a_trigger',
                'Table' => 'test_table2',
                'Timing' => 'BEFORE',
                'Event' => 'UPDATE',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer2@localhost',
            ]),
            Trigger::tryFromArray([
                'Trigger' => 'test_trigger',
                'Table' => 'test_table',
                'Timing' => 'AFTER',
                'Event' => 'INSERT',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer@localhost',
            ]),
        ];
        self::assertEquals($expected, $triggers);
    }

    public function testGetDetails2(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SHOW TRIGGERS FROM `test_db` LIKE 'test_table2';",
            [['a_trigger', 'UPDATE', 'test_table2', 'BEGIN END', 'BEFORE', 'definer2@localhost']],
            ['Trigger', 'Event', 'Table', 'Statement', 'Timing', 'Definer'],
        );

        $triggers = Triggers::getDetails($this->createDatabaseInterface($dbiDummy), 'test_db', 'test_table2');
        $expected = [
            Trigger::tryFromArray([
                'Trigger' => 'a_trigger',
                'Table' => 'test_table2',
                'Timing' => 'BEFORE',
                'Event' => 'UPDATE',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer2@localhost',
            ]),
        ];
        self::assertEquals($expected, $triggers);
    }

    public function testGetDetails3(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            "SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= 'test_db'",
            [['test_db', 'test_trigger', 'DELETE', 'test_table', 'AFTER', 'BEGIN END', 'test_db', 'test_table', 'definer@localhost']],
            ['TRIGGER_SCHEMA', 'TRIGGER_NAME', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE', 'ACTION_TIMING', 'ACTION_STATEMENT', 'EVENT_OBJECT_SCHEMA', 'EVENT_OBJECT_TABLE', 'DEFINER'],
        );
        // phpcs:enable

        $triggers = Triggers::getDetails($this->createDatabaseInterface($dbiDummy), 'test_db');
        $expected = [
            Trigger::tryFromArray([
                'Trigger' => 'test_trigger',
                'Table' => 'test_table',
                'Timing' => 'AFTER',
                'Event' => 'DELETE',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer@localhost',
            ]),
        ];
        self::assertEquals($expected, $triggers);
    }

    public function testGetDetails4(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->addResult(
            "SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= 'test_db' AND EVENT_OBJECT_TABLE COLLATE utf8_bin = 'test_table';",
            [['test_db', 'test_trigger', 'DELETE', 'test_table', 'AFTER', 'BEGIN END', 'test_db', 'test_table', 'definer@localhost']],
            ['TRIGGER_SCHEMA', 'TRIGGER_NAME', 'EVENT_MANIPULATION', 'EVENT_OBJECT_TABLE', 'ACTION_TIMING', 'ACTION_STATEMENT', 'EVENT_OBJECT_SCHEMA', 'EVENT_OBJECT_TABLE', 'DEFINER'],
        );
        // phpcs:enable

        $triggers = Triggers::getDetails($this->createDatabaseInterface($dbiDummy), 'test_db', 'test_table');
        $expected = [
            Trigger::tryFromArray([
                'Trigger' => 'test_trigger',
                'Table' => 'test_table',
                'Timing' => 'AFTER',
                'Event' => 'DELETE',
                'Statement' => 'BEGIN END',
                'Definer' => 'definer@localhost',
            ]),
        ];
        self::assertEquals($expected, $triggers);
    }
}
