<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Util;
use ReflectionMethod;

/**
 * @covers \PhpMyAdmin\Tracker
 */
class TrackerTest extends AbstractTestCase
{
    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = '';
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = '';
        $GLOBALS['cfg']['Server']['tracking_add_drop_database'] = '';
        $GLOBALS['cfg']['Server']['tracking_default_statements'] = '';
        $GLOBALS['cfg']['Server']['tracking_version_auto_create'] = '';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['export_type'] = null;

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'trackingwork' => true,
            'tracking' => 'tracking',
        ])->toArray();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
    }

    /**
     * Test for Tracker::enable
     */
    public function testEnabled(): void
    {
        $this->assertFalse(
            Cache::has(Tracker::TRACKER_ENABLED_CACHE_KEY)
        );
        Tracker::enable();
        $this->assertTrue(
            Cache::get(Tracker::TRACKER_ENABLED_CACHE_KEY)
        );
    }

    /**
     * Test for Tracker::isActive()
     */
    public function testIsActive(): void
    {
        $this->assertFalse(
            Cache::has(Tracker::TRACKER_ENABLED_CACHE_KEY)
        );

        $this->assertFalse(
            Tracker::isActive()
        );

        Tracker::enable();

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        $this->assertFalse(
            Tracker::isActive()
        );

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'trackingwork' => true,
            'db' => 'pmadb',
            'tracking' => 'tracking',
        ])->toArray();

        $this->assertTrue(
            Tracker::isActive()
        );
    }

    /**
     * Test for Tracker::getTableName()
     *
     * @param string $string   String to test against
     * @param string $expected Expected Table Name
     *
     * @dataProvider getTableNameData
     */
    public function testGetTableName(string $string, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->callFunction(null, Tracker::class, 'getTableName', [$string])
        );
    }

    /**
     * Data Provider for testGetTableName
     *
     * @return array Test data
     */
    public function getTableNameData(): array
    {
        return [
            [
                '`tbl`;',
                'tbl',
            ],
            [
                ' `pma.table` ',
                'table',
            ],
            [
                " `pma.table\nfoobar` ",
                'table',
            ],
        ];
    }

    /**
     * Test for Tracker::isTracked()
     */
    public function testIsTracked(): void
    {
        $this->assertFalse(
            Cache::has(Tracker::TRACKER_ENABLED_CACHE_KEY)
        );

        $this->assertFalse(
            Tracker::isTracked('', '')
        );

        Tracker::enable();

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([])->toArray();

        $this->assertFalse(
            Tracker::isTracked('', '')
        );

        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'trackingwork' => true,
            'db' => 'pmadb',
            'tracking' => 'tracking',
        ])->toArray();

        $this->assertTrue(
            Tracker::isTracked('pma_test_db', 'pma_test_table')
        );

        $this->assertFalse(
            Tracker::isTracked('pma_test_db', 'pma_test_table2')
        );
    }

    /**
     * Test for Tracker::getLogComment()
     */
    public function testGetLogComment(): void
    {
        $date = Util::date('Y-m-d H:i:s');
        $GLOBALS['cfg']['Server']['user'] = 'pma_test_user';

        $this->assertEquals(
            '# log ' . $date . " pma_test_user\n",
            Tracker::getLogComment()
        );
    }

    /**
     * Test for Tracker::createVersion()
     */
    public function testCreateVersion(): void
    {
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = true;
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = true;
        $GLOBALS['cfg']['Server']['user'] = 'pma_test_user';

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
            [
                'Field' => 'field1',
                'Type' => 'int(11)',
                'Key' => 'PRI',
            ],
            [
                'Field' => 'field2',
                'Type' => 'text',
                'Key' => '',
            ],
        ];
        $dbi->expects($this->once())->method('getColumns')
            ->with('pma_test', 'pma_tbl')
            ->will($this->returnValue($getColumnsResult));

        $getIndexesResult = [
            [
                'Table' => 'pma_tbl',
                'Field' => 'field1',
                'Key' => 'PRIMARY',
            ],
        ];
        $dbi->expects($this->once())->method('getTableIndexes')
            ->with('pma_test', 'pma_tbl')
            ->will($this->returnValue($getIndexesResult));

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->withConsecutive(
                ["SHOW TABLE STATUS FROM `pma_test` WHERE Name = 'pma_tbl'"],
                ['USE `pma_test`'],
                ['SHOW CREATE TABLE `pma_test`.`pma_tbl`']
            )
            ->willReturnOnConsecutiveCalls($resultStub, $resultStub, $resultStub);

        $dbi->expects($this->any())->method('query')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $dbi->expects($this->any())->method('getCompatibilities')
            ->will($this->returnValue([]));

        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Tracker::createVersion('pma_test', 'pma_tbl', '1', '11', true));
    }

    /**
     * Test for Tracker::deleteTracking()
     */
    public function testDeleteTracking(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sql_query = "/*NOTRACK*/\n"
            . 'DELETE FROM `pmadb`.`tracking`'
            . " WHERE `db_name` = 'testdb'"
            . " AND `table_name` = 'testtable'";

        $dbi->expects($this->exactly(1))
            ->method('queryAsControlUser')
            ->with($sql_query)
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Tracker::deleteTracking('testdb', 'testtable'));
    }

    /**
     * Test for Tracker::createDatabaseVersion()
     */
    public function testCreateDatabaseVersion(): void
    {
        $GLOBALS['cfg']['Server']['tracking_add_drop_table'] = true;
        $GLOBALS['cfg']['Server']['tracking_add_drop_view'] = true;
        $GLOBALS['cfg']['Server']['user'] = 'pma_test_user';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expectedMainQuery = '/*NOTRACK*/' . "\n" . 'INSERT INTO `pmadb`.`tracking` (db_name, table_name, version,'
            . ' date_created, date_updated, schema_snapshot, schema_sql, data_sql, tracking)'
            . ' values (\'pma_test\', \'\', \'1\', \'%d-%d-%d %d:%d:%d\', \'%d-%d-%d %d:%d:%d\','
            . ' \'\', \'# log %d-%d-%d %d:%d:%d pma_test_user' . "\n" . 'SHOW DATABASES\', \'' . "\n"
            . '\', \'CREATE DATABASE,ALTER DATABASE,DROP DATABASE\')';

        $dbi->expects($this->exactly(1))
            ->method('queryAsControlUser')
            ->with($this->matches($expectedMainQuery))
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertTrue(Tracker::createDatabaseVersion('pma_test', '1', 'SHOW DATABASES'));
    }

    /**
     * Test for Tracker::changeTracking(). This test is also invoked by two
     * other tests: testActivateTracking() and testDeactivateTracking()
     *
     * @param string     $dbname    Database name
     * @param string     $tablename Table name
     * @param string     $version   Version
     * @param string|int $new_state State to change to
     * @param string     $type      Type of test
     */
    public function testChangeTracking(
        string $dbname = 'pma_db',
        string $tablename = 'pma_tbl',
        string $version = '0.1',
        $new_state = '1',
        ?string $type = null
    ): void {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultStub = $this->createMock(DummyResult::class);

        $sql_query = 'UPDATE `pmadb`.`tracking` SET `tracking_active` = ' .
        "'" . $new_state . "'" .
        " WHERE `db_name` = '" . $dbname . "'" .
        " AND `table_name` = '" . $tablename . "'" .
        " AND `version` = '" . $version . "'";

        $dbi->expects($this->exactly(1))
            ->method('queryAsControlUser')
            ->with($sql_query)
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        if ($type === null) {
            $method = new ReflectionMethod(Tracker::class, 'changeTracking');
            $method->setAccessible(true);
            $method->invoke(null, $dbname, $tablename, $version, $new_state);
        } elseif ($type === 'activate') {
            Tracker::activateTracking($dbname, $tablename, $version);
        } elseif ($type === 'deactivate') {
            Tracker::deactivateTracking($dbname, $tablename, $version);
        }

        // What's the success criteria? What is the expected result?
    }

    /**
     * Test for Tracker::testChangeTrackingData()
     */
    public function testChangeTrackingData(): void
    {
        $this->assertFalse(
            Tracker::changeTrackingData('', '', '', '', '')
        );

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sql_query_1 = 'UPDATE `pmadb`.`tracking`' .
        " SET `schema_sql` = '# new_data_processed'" .
        " WHERE `db_name` = 'pma_db'" .
        " AND `table_name` = 'pma_table'" .
        " AND `version` = '1.0'";

        $date = Util::date('Y-m-d H:i:s');

        $new_data = [
            [
                'username' => 'user1',
                'statement' => 'test_statement1',
            ],
            [
                'username' => 'user2',
                'statement' => 'test_statement2',
            ],
        ];

        $sql_query_2 = 'UPDATE `pmadb`.`tracking`' .
        " SET `data_sql` = '# log " . $date . " user1test_statement1\n" .
        '# log ' . $date . " user2test_statement2\n'" .
        " WHERE `db_name` = 'pma_db'" .
        " AND `table_name` = 'pma_table'" .
        " AND `version` = '1.0'";

        $resultStub1 = $this->createMock(DummyResult::class);
        $resultStub2 = $this->createMock(DummyResult::class);

        $dbi->method('queryAsControlUser')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            $sql_query_1,
                            $resultStub1,
                        ],
                        [
                            $sql_query_2,
                            $resultStub2,
                        ],
                    ]
                )
            );

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;

        $this->assertTrue(
            Tracker::changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                'DDL',
                '# new_data_processed'
            )
        );

        $this->assertTrue(
            Tracker::changeTrackingData(
                'pma_db',
                'pma_table',
                '1.0',
                'DML',
                $new_data
            )
        );
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
     * Test for PMA_Tracker::getTrackedData()
     *
     * @param array $fetchArrayReturn Value to be returned by mocked fetchArray
     * @param array $expectedArray    Expected array
     *
     * @dataProvider getTrackedDataProvider
     */
    public function testGetTrackedData(array $fetchArrayReturn, array $expectedArray): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('queryAsControlUser')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue($fetchArrayReturn));

        $dbi->expects($this->any())
            ->method('escapeString')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            "pma'db",
                            DatabaseInterface::CONNECT_USER,
                            "pma\'db",
                        ],
                        [
                            "pma'table",
                            DatabaseInterface::CONNECT_USER,
                            "pma\'table",
                        ],
                        [
                            '1.0',
                            DatabaseInterface::CONNECT_USER,
                            '1.0',
                        ],
                    ]
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $result = Tracker::getTrackedData("pma'db", "pma'table", '1.0');

        $this->assertEquals($expectedArray, $result);
    }

    /**
     * Data provider for testGetTrackedData
     *
     * @return array Test data
     */
    public function getTrackedDataProvider(): array
    {
        $fetchArrayReturn = [
            [
                'schema_sql' => "# log 20-03-2013 23:33:58 user1\nstat1" .
                "# log 20-03-2013 23:39:58 user2\n",
                'data_sql' => '# log ',
                'schema_snapshot' => 'dataschema',
                'tracking' => 'SELECT, DELETE',
            ],
        ];

        $data = [
            [
                'date_from' => '20-03-2013 23:33:58',
                'date_to' => '20-03-2013 23:39:58',
                'ddlog' => [
                    [
                        'date' => '20-03-2013 23:33:58',
                        'username' => 'user1',
                        'statement' => "\nstat1",
                    ],
                    [
                        'date' => '20-03-2013 23:39:58',
                        'username' => 'user2',
                        'statement' => '',
                    ],
                ],
                'dmlog' => [],
                'schema_snapshot' => 'dataschema',
                'tracking' => 'SELECT, DELETE',
            ],
        ];

        $fetchArrayReturn[1] = [
            'schema_sql' => "# log 20-03-2012 23:33:58 user1\n" .
            "# log 20-03-2012 23:39:58 user2\n",
            'data_sql' => "# log 20-03-2013 23:33:58 user3\n" .
            "# log 20-03-2013 23:39:58 user4\n",
            'schema_snapshot' => 'dataschema',
            'tracking' => 'SELECT, DELETE',
        ];

        $data[1] = [
            'date_from' => '20-03-2012 23:33:58',
            'date_to' => '20-03-2013 23:39:58',
            'ddlog' => [
                [
                    'date' => '20-03-2012 23:33:58',
                    'username' => 'user1',
                    'statement' => '',
                ],
                [
                    'date' => '20-03-2012 23:39:58',
                    'username' => 'user2',
                    'statement' => '',
                ],
            ],
            'dmlog' => [
                [
                    'date' => '20-03-2013 23:33:58',
                    'username' => 'user3',
                    'statement' => '',
                ],
                [
                    'date' => '20-03-2013 23:39:58',
                    'username' => 'user4',
                    'statement' => '',
                ],
            ],
            'schema_snapshot' => 'dataschema',
            'tracking' => 'SELECT, DELETE',
        ];

        return [
            [
                $fetchArrayReturn[0],
                $data[0],
            ],
            [
                $fetchArrayReturn[1],
                $data[1],
            ],
        ];
    }

    /**
     * Test for Tracker::parseQuery
     *
     * @param string      $query                  Query to parse
     * @param string      $type                   Expected type
     * @param string      $identifier             Expected identifier
     * @param string|null $tablename              Expected tablename
     * @param string|null $db                     Expected dbname
     * @param string|null $tablename_after_rename Expected name after rename
     *
     * @dataProvider parseQueryData
     */
    public function testParseQuery(
        string $query,
        string $type,
        string $identifier,
        ?string $tablename,
        ?string $db = null,
        ?string $tablename_after_rename = null
    ): void {
        $result = Tracker::parseQuery($query);

        $this->assertEquals($type, $result['type']);

        $this->assertEquals($identifier, $result['identifier']);

        $this->assertEquals($tablename, $result['tablename']);

        if ($db) {
            $this->assertEquals($db, $GLOBALS['db']);
        }

        if (! $tablename_after_rename) {
            return;
        }

        $this->assertEquals($result['tablename_after_rename'], $tablename_after_rename);
    }

    /**
     * Data provider for testParseQuery
     *
     * @return array Test data
     */
    public function parseQueryData(): array
    {
        // query
        // type
        // identifier
        // table name
        // db (optional)
        // table name after rename (optional)
        $query = [];
        /* TODO: Should test fail when USE is in conjunction with * identifiers?
        $query[] = array(
            " - USE db1;\n- CREATE VIEW db1.v AS SELECT * FROM t;",
            "DDL",
            "CREATE VIEW",
            "v",
            "db1"
        );
        */
        $query[] = [
            'CREATE VIEW v AS SELECT * FROM t;',
            'DDL',
            'CREATE VIEW',
            'v',
        ];
        $query[] = [
            'ALTER VIEW db1.v AS SELECT col1, col2, col3, col4 FROM t',
            'DDL',
            'ALTER VIEW',
            'v',
        ];
        $query[] = [
            'DROP VIEW db1.v;',
            'DDL',
            'DROP VIEW',
            'v',
        ];
        $query[] = [
            'DROP VIEW IF EXISTS db1.v;',
            'DDL',
            'DROP VIEW',
            'v',
        ];
        $query[] = [
            'CREATE DATABASE db1;',
            'DDL',
            'CREATE DATABASE',
            '',
            'db1',
        ];
        $query[] = [
            'ALTER DATABASE db1;',
            'DDL',
            'ALTER DATABASE',
            '',
        ];
        $query[] = [
            'DROP DATABASE db1;',
            'DDL',
            'DROP DATABASE',
            '',
            'db1',
        ];
        $query[] = [
            'CREATE TABLE db1.t1 (c1 INT);',
            'DDL',
            'CREATE TABLE',
            't1',
        ];
        $query[] = [
            'ALTER TABLE db1.t1 ADD c2 TEXT;',
            'DDL',
            'ALTER TABLE',
            't1',
        ];
        $query[] = [
            'DROP TABLE db1.t1',
            'DDL',
            'DROP TABLE',
            't1',
        ];
        $query[] = [
            'DROP TABLE IF EXISTS db1.t1',
            'DDL',
            'DROP TABLE',
            't1',
        ];
        $query[] = [
            'CREATE INDEX ind ON db1.t1 (c2(10));',
            'DDL',
            'CREATE INDEX',
            't1',
        ];
        $query[] = [
            'CREATE UNIQUE INDEX ind ON db1.t1 (c2(10));',
            'DDL',
            'CREATE INDEX',
            't1',
        ];
        $query[] = [
            'CREATE SPATIAL INDEX ind ON db1.t1 (c2(10));',
            'DDL',
            'CREATE INDEX',
            't1',
        ];
        $query[] = [
            'DROP INDEX ind ON db1.t1;',
            'DDL',
            'DROP INDEX',
            't1',
        ];
        $query[] = [
            'RENAME TABLE db1.t1 TO db1.t2',
            'DDL',
            'RENAME TABLE',
            't1',
            '',
            't2',
        ];
        $query[] = [
            'UPDATE db1.t1 SET a = 2',
            'DML',
            'UPDATE',
            't1',
        ];
        $query[] = [
            'INSERT INTO db1.t1 (a, b, c) VALUES(1, 2, 3)',
            'DML',
            'INSERT',
            't1',
        ];
        $query[] = [
            'DELETE FROM db1.t1',
            'DML',
            'DELETE',
            't1',
        ];
        $query[] = [
            'TRUNCATE db1.t1',
            'DML',
            'TRUNCATE',
            't1',
        ];
        $query[] = [
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
        ];

        return $query;
    }
}
