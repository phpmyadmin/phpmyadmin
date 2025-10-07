<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Util;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;

#[CoversClass(Tracker::class)]
class TrackerTest extends AbstractTestCase
{
    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();

        /**
         * SET these to avoid undefined index error
         */
        $config = Config::getInstance();
        $config->selectedServer['tracking_add_drop_table'] = '';
        $config->selectedServer['tracking_add_drop_view'] = '';
        $config->selectedServer['tracking_add_drop_database'] = '';
        $config->selectedServer['tracking_default_statements'] = '';
        $config->selectedServer['tracking_version_auto_create'] = '';
        $config->selectedServer['DisableIS'] = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::TRACKING => 'tracking',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    /**
     * Test for Tracker::enable
     */
    public function testEnabled(): void
    {
        self::assertFalse(Tracker::isEnabled());
        Tracker::enable();
        self::assertTrue(Tracker::isEnabled());
        Tracker::disable();
        self::assertFalse(Tracker::isEnabled());
    }

    /**
     * Test for Tracker::isActive()
     */
    public function testIsActive(): void
    {
        self::assertFalse(Tracker::isEnabled());

        self::assertFalse(
            Tracker::isActive(),
        );

        Tracker::enable();

        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        self::assertFalse(
            Tracker::isActive(),
        );

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING => 'tracking',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        self::assertTrue(
            Tracker::isActive(),
        );
    }

    /**
     * Test for Tracker::isTracked()
     */
    public function testIsTracked(): void
    {
        self::assertFalse(Tracker::isEnabled());

        self::assertFalse(
            Tracker::isTracked('', ''),
        );

        Tracker::enable();

        $relationParameters = RelationParameters::fromArray([]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        self::assertFalse(
            Tracker::isTracked('', ''),
        );

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::TRACKING_WORK => true,
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::TRACKING => 'tracking',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        self::assertTrue(
            Tracker::isTracked('pma_test_db', 'pma_test_table'),
        );

        self::assertFalse(
            Tracker::isTracked('pma_test_db', 'pma_test_table2'),
        );
    }

    /**
     * Test for Tracker::getLogComment()
     */
    public function testGetLogComment(): void
    {
        $date = Util::date('Y-m-d H:i:s');
        Config::getInstance()->selectedServer['user'] = 'pma_test_user';

        self::assertSame(
            '# log ' . $date . " pma_test_user\n",
            Tracker::getLogComment(),
        );
    }

    /**
     * Test for Tracker::createVersion()
     */
    public function testCreateVersion(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['tracking_add_drop_table'] = true;
        $config->selectedServer['tracking_add_drop_view'] = true;
        $config->selectedServer['user'] = 'pma_test_user';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * set up mock objects
         * passing null to with() for an argument is equivalent
         * to passing $this->anything()
         */

        $getColumnsResult = [
            new Column('field1', 'int(11)', null, false, 'PRI', null, '', '', ''),
            new Column('field2', 'text', null, false, '', null, '', '', ''),
        ];
        $dbi->expects(self::once())->method('getColumns')
            ->with('pma_test', 'pma_tbl')
            ->willReturn($getColumnsResult);

        $getIndexesResult = [['Table' => 'pma_tbl', 'Field' => 'field1', 'Key' => 'PRIMARY']];
        $dbi->expects(self::once())->method('getTableIndexes')
            ->with('pma_test', 'pma_tbl')
            ->willReturn($getIndexesResult);

        $showTableStatusQuery = 'SHOW TABLE STATUS FROM `pma_test` WHERE Name = \'pma_tbl\'';
        $useStatement = 'USE `pma_test`';
        $showCreateTableQuery = 'SHOW CREATE TABLE `pma_test`.`pma_tbl`';
        $dbi->expects(self::exactly(3))->method('tryQuery')->willReturnMap([
            [$showTableStatusQuery, ConnectionType::User, false, true, $resultStub],
            [$useStatement, ConnectionType::User, false, true, $resultStub],
            [$showCreateTableQuery, ConnectionType::User, false, true, $resultStub],
        ]);

        $dbi->expects(self::any())->method('query')
            ->willReturn($resultStub);

        $dbi->expects(self::any())->method('getCompatibilities')
            ->willReturn([]);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        self::assertTrue(Tracker::createVersion('pma_test', 'pma_tbl', '1', '11', true));
    }

    /**
     * Test for Tracker::createDatabaseVersion()
     */
    public function testCreateDatabaseVersion(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['tracking_add_drop_table'] = true;
        $config->selectedServer['tracking_add_drop_view'] = true;
        $config->selectedServer['user'] = 'pma_test_user';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedMainQuery = '/*NOTRACK*/' . "\n" . 'INSERT INTO `pmadb`.`tracking` (db_name, table_name, version,'
            . ' date_created, date_updated, schema_snapshot, schema_sql, data_sql, tracking)'
            . ' values (\'pma_test\', \'\', \'1\', \'%d-%d-%d %d:%d:%d\', \'%d-%d-%d %d:%d:%d\','
            . ' \'\', \'# log %d-%d-%d %d:%d:%d pma_test_user' . "\n" . 'SHOW DATABASES\', \'' . "\n"
            . '\', \'CREATE DATABASE,ALTER DATABASE,DROP DATABASE\')';

        $dbi->expects(self::exactly(1))
            ->method('queryAsControlUser')
            ->with(self::matches($expectedMainQuery))
            ->willReturn($resultStub);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        self::assertTrue(Tracker::createDatabaseVersion('pma_test', '1', 'SHOW DATABASES'));
    }

    /**
     * Test for Tracker::changeTracking(). This test is also invoked by two
     * other tests: testActivateTracking() and testDeactivateTracking()
     *
     * @param string     $dbname    Database name
     * @param string     $tablename Table name
     * @param string     $version   Version
     * @param string|int $newState  State to change to
     * @param string     $type      Type of test
     */
    public function testChangeTracking(
        string $dbname = 'pma_db',
        string $tablename = 'pma_tbl',
        string $version = '0.1',
        string|int $newState = '1',
        string|null $type = null,
    ): void {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultStub = $this->createMock(DummyResult::class);

        $sqlQuery = 'UPDATE `pmadb`.`tracking` SET `tracking_active` = ' . $newState .
        " WHERE `db_name` = '" . $dbname . "'" .
        " AND `table_name` = '" . $tablename . "'" .
        " AND `version` = '" . $version . "'";

        $dbi->expects(self::exactly(1))
            ->method('queryAsControlUser')
            ->with($sqlQuery)
            ->willReturn($resultStub);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;

        if ($type === null) {
            $method = new ReflectionMethod(Tracker::class, 'changeTracking');
            $method->invoke(null, $dbname, $tablename, $version, $newState);
        } elseif ($type === 'activate') {
            Tracker::activateTracking($dbname, $tablename, $version);
        } elseif ($type === 'deactivate') {
            Tracker::deactivateTracking($dbname, $tablename, $version);
        }

        // What's the success criteria? What is the expected result?
    }

    /**
     * Test for Tracker::activateTracking()
     */
    public function testActivateTracking(): void
    {
        $this->testChangeTracking('pma_db', 'pma_tbl', '0.1', 1, 'activate');
    }

    /**
     * Test for Tracker::deactivateTracking()
     */
    public function testDeactivateTracking(): void
    {
        $this->testChangeTracking('pma_db', 'pma_tbl', '0.1', '0', 'deactivate');
    }

    /**
     * Test for Tracker::parseQuery
     *
     * @param string      $query                Query to parse
     * @param string      $type                 Expected type
     * @param string      $identifier           Expected identifier
     * @param string|null $tableName            Expected tablename
     * @param string|null $db                   Expected dbname
     * @param string|null $tableNameAfterRename Expected name after rename
     */
    #[DataProvider('parseQueryData')]
    public function testParseQuery(
        string $query,
        string $type,
        string $identifier,
        string|null $tableName,
        string|null $db = null,
        string|null $tableNameAfterRename = null,
    ): void {
        $result = Tracker::parseQuery($query);

        self::assertSame($type, $result['type']);

        self::assertSame($identifier, $result['identifier']);

        self::assertSame($tableName, $result['tablename']);

        if ($db !== null && $db !== '') {
            self::assertSame($db, Current::$database);
        }

        if ($tableNameAfterRename === null || $tableNameAfterRename === '') {
            return;
        }

        self::assertSame($result['tablename_after_rename'], $tableNameAfterRename);
    }

    /**
     * Data provider for testParseQuery
     *
     * @return array<array<string|null>>
     */
    public static function parseQueryData(): array
    {
        return [
            /* TODO: Should test fail when USE is in conjunction with * identifiers?
               $query[] = array(
                   " - USE db1;\n- CREATE VIEW db1.v AS SELECT * FROM t;",
                   "DDL",
                   "CREATE VIEW",
                   "v",
                   "db1"
               );
               */
            ['CREATE VIEW v AS SELECT * FROM t;', 'DDL', 'CREATE VIEW', 'v'],
            ['ALTER VIEW db1.v AS SELECT col1, col2, col3, col4 FROM t', 'DDL', 'ALTER VIEW', 'v'],
            ['DROP VIEW db1.v;', 'DDL', 'DROP VIEW', 'v'],
            ['DROP VIEW IF EXISTS db1.v;', 'DDL', 'DROP VIEW', 'v'],
            ['CREATE DATABASE db1;', 'DDL', 'CREATE DATABASE', '', 'db1'],
            ['ALTER DATABASE db1;', 'DDL', 'ALTER DATABASE', ''],
            ['DROP DATABASE db1;', 'DDL', 'DROP DATABASE', '', 'db1'],
            ['CREATE TABLE db1.t1 (c1 INT);', 'DDL', 'CREATE TABLE', 't1'],
            ['ALTER TABLE db1.t1 ADD c2 TEXT;', 'DDL', 'ALTER TABLE', 't1'],
            ['DROP TABLE db1.t1', 'DDL', 'DROP TABLE', 't1'],
            ['DROP TABLE IF EXISTS db1.t1', 'DDL', 'DROP TABLE', 't1'],
            ['CREATE INDEX ind ON db1.t1 (c2(10));', 'DDL', 'CREATE INDEX', 't1'],
            ['CREATE UNIQUE INDEX ind ON db1.t1 (c2(10));', 'DDL', 'CREATE INDEX', 't1'],
            ['CREATE SPATIAL INDEX ind ON db1.t1 (c2(10));', 'DDL', 'CREATE INDEX', 't1'],
            ['DROP INDEX ind ON db1.t1;', 'DDL', 'DROP INDEX', 't1'],
            ['RENAME TABLE db1.t1 TO db1.t2', 'DDL', 'RENAME TABLE', 't1', '', 't2'],
            ['UPDATE db1.t1 SET a = 2', 'DML', 'UPDATE', 't1'],
            ['INSERT INTO db1.t1 (a, b, c) VALUES(1, 2, 3)', 'DML', 'INSERT', 't1'],
            ['DELETE FROM db1.t1', 'DML', 'DELETE', 't1'],
            ['TRUNCATE db1.t1', 'DML', 'TRUNCATE', 't1'],
            [
                'create table event(' . "\n"
                . 'eventID varchar(10) not null,' . "\n"
                . 'b char(30),' . "\n"
                . 'c varchar(20),' . "\n"
                . 'd TIME,' . "\n"
                . 'e Date,' . "\n"
                . 'f int,' . "\n"
                . 'g char(70),' . "\n"
                . 'h char(90),' . "\n"
                . 'primary key(eventID)' . "\n"
                . ')' . "\n",
                'DDL',
                'CREATE TABLE',
                null,// switch this to 'event' when sql-parse is fixed
            ],
        ];
    }
}
