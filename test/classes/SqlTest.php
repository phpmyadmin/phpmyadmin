<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use ReflectionMethod;
use stdClass;

use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_VAR_STRING;
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Sql
 */
class SqlTest extends AbstractTestCase
{
    /** @var Sql */
    private $sql;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['SendErrorReports'] = 'ask';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'] = true;
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'structure';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = '';
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['Confirm'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['cfg']['enable_drag_drop_import'] = true;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $relation = new Relation($GLOBALS['dbi']);
        $this->sql = new Sql(
            $GLOBALS['dbi'],
            $relation,
            new RelationCleanup($GLOBALS['dbi'], $relation),
            new Operations($GLOBALS['dbi'], $relation),
            new Transformations(),
            new Template()
        );
    }

    /**
     * Test for getSqlWithLimitClause
     */
    public function testGetSqlWithLimitClause(): void
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['pos'] = 1;
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 2;

        $analyzed_sql_results = $this->parseAndAnalyze('SELECT * FROM test LIMIT 0, 10');
        self::assertSame(
            'SELECT * FROM test LIMIT 1, 2 ',
            $this->callFunction($this->sql, Sql::class, 'getSqlWithLimitClause', [&$analyzed_sql_results])
        );
    }

    /**
     * Test for isRememberSortingOrder
     */
    public function testIsRememberSortingOrder(): void
    {
        // Test environment.
        $GLOBALS['cfg']['RememberSorting'] = true;

        self::assertTrue($this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
            $this->parseAndAnalyze('SELECT * FROM tbl'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
            $this->parseAndAnalyze('SELECT col FROM tbl'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
            $this->parseAndAnalyze('SELECT 1'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
            $this->parseAndAnalyze('SELECT col1, col2 FROM tbl'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
            $this->parseAndAnalyze('SELECT COUNT(*) from tbl'),
        ]));
    }

    /**
     * Test for isAppendLimitClause
     */
    public function testIsAppendLimitClause(): void
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        self::assertTrue($this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
            $this->parseAndAnalyze('SELECT * FROM tbl'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
            $this->parseAndAnalyze('SELECT * from tbl LIMIT 0, 10'),
        ]));
    }

    public function testIsJustBrowsing(): void
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        self::assertTrue(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * FROM db.tbl'),
            null
        ));

        self::assertTrue(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * FROM tbl WHERE 1'),
            null
        ));

        self::assertFalse(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * from tbl1, tbl2 LIMIT 0, 10'),
            null
        ));
    }

    /**
     * Test for isDeleteTransformationInfo
     */
    public function testIsDeleteTransformationInfo(): void
    {
        self::assertTrue($this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
            $this->parseAndAnalyze('ALTER TABLE tbl DROP COLUMN col'),
        ]));

        self::assertTrue($this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
            $this->parseAndAnalyze('DROP TABLE tbl'),
        ]));

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
            $this->parseAndAnalyze('SELECT * from tbl'),
        ]));
    }

    /**
     * Test for hasNoRightsToDropDatabase
     */
    public function testHasNoRightsToDropDatabase(): void
    {
        self::assertTrue($this->sql->hasNoRightsToDropDatabase(
            $this->parseAndAnalyze('DROP DATABASE db'),
            false,
            false
        ));

        self::assertFalse($this->sql->hasNoRightsToDropDatabase(
            $this->parseAndAnalyze('DROP TABLE tbl'),
            false,
            false
        ));

        self::assertFalse($this->sql->hasNoRightsToDropDatabase(
            $this->parseAndAnalyze('SELECT * from tbl'),
            false,
            false
        ));
    }

    /**
     * Should return false if all columns are not from the same table
     */
    public function testWithMultipleTables(): void
    {
        $col1 = new stdClass();
        $col1->table = 'table1';
        $col2 = new stdClass();
        $col2->table = 'table1';
        $col3 = new stdClass();
        $col3->table = 'table3';

        $fields_meta = [
            $col1,
            $col2,
            $col3,
        ];
        self::assertFalse($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));

        // should not matter on where the odd column occurs
        $fields_meta = [
            $col2,
            $col3,
            $col1,
        ];
        self::assertFalse($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));

        $fields_meta = [
            $col3,
            $col1,
            $col2,
        ];
        self::assertFalse($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));
    }

    /**
     * Should return true if all the columns are from the same table
     */
    public function testWithSameTable(): void
    {
        $col1 = new stdClass();
        $col1->table = 'table1';
        $col2 = new stdClass();
        $col2->table = 'table1';
        $col3 = new stdClass();
        $col3->table = 'table1';
        $fields_meta = [
            $col1,
            $col2,
            $col3,
        ];

        self::assertTrue($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));
    }

    /**
     * Should return true even if function columns (table is '') occur when others
     * are from the same table.
     */
    public function testWithFunctionColumns(): void
    {
        $col1 = new stdClass();
        $col1->table = 'table1';
        $col2 = new stdClass();
        $col2->table = '';
        $col3 = new stdClass();
        $col3->table = 'table1';

        $fields_meta = [
            $col1,
            $col2,
            $col3,
        ];
        self::assertTrue($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));

        // should not matter on where the function column occurs
        $fields_meta = [
            $col2,
            $col3,
            $col1,
        ];
        self::assertTrue($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));

        $fields_meta = [
            $col3,
            $col1,
            $col2,
        ];
        self::assertTrue($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));
    }

    /**
     * We can not say all the columns are from the same table if all the columns
     * are function columns (table is '')
     */
    public function testWithOnlyFunctionColumns(): void
    {
        $col1 = new stdClass();
        $col1->table = '';
        $col2 = new stdClass();
        $col2->table = '';
        $col3 = new stdClass();
        $col3->table = '';
        $fields_meta = [
            $col1,
            $col2,
            $col3,
        ];

        self::assertFalse($this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta]));
    }

    /**
     * @return mixed
     */
    private function parseAndAnalyze(string $sqlQuery)
    {
        global $db;

        [$analyzedSqlResults] = ParseAnalyze::sqlQuery($sqlQuery, $db);

        return $analyzedSqlResults;
    }

    public static function dataProviderCountQueryResults(): array
    {
        // sql query
        // session tmpval
        // num rows
        // result
        // just browsing
        return [
            'join on SELECT results with *' => [
                // -- Showing rows 0 - 49 (164056 total, 0 in query, Query took 0.1498 seconds.)
                'select * from game_auth_logs l join ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) last_log on last_log.id = l.id;',
                ['max_rows' => 50, 'pos' => 0],
                164056,
                50,
                false,
                'SELECT COUNT(*) FROM (SELECT 1 FROM game_auth_logs AS `l` JOIN ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) AS `last_log` ON last_log.id = l.id'
                    . ' ) as cnt',
            ],
            'join on SELECT results with alias.*' => [
                // -- Showing rows 0 - 24 (267 total, Query took 0.1533 seconds.)
                'select l.* from game_auth_logs l join ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) last_log on last_log.id = l.id;',
                ['max_rows' => 50, 'pos' => 0],
                267,
                50,
                false,
                'SELECT COUNT(*) FROM (SELECT 1 FROM game_auth_logs AS `l` JOIN ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) AS `last_log` ON last_log.id = l.id'
                    . ' ) as cnt',
            ],
            [
                'SELECT * FROM company_users WHERE id != 0 LIMIT 0, 10',
                ['max_rows' => 250],
                -1,
                -1,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => -1,
                ],
                -1,
                -2,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => -1,
                ],
                -1,
                -2,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => 250,
                ],
                -1,
                249,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => 4,
                ],
                2,
                6,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 'all',
                    'pos' => 4,
                ],
                2,
                2,
            ],
            [
                null,
                [],
                2,
                0,
            ],
            [

                'SELECT * FROM company_users LIMIT 1,4',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                20,

            ],
            [

                'SELECT * FROM company_users',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                4,
            ],
            [

                'SELECT * FROM company_users WHERE not_working_count != 0',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                0,
            ],
            [

                'SELECT * FROM company_users WHERE working_count = 0',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                15,

            ],
            [
                'UPDATE company_users SET a=1 WHERE working_count = 0',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                20,
            ],
            [
                'UPDATE company_users SET a=1 WHERE working_count = 0',
                [
                    'max_rows' => 'all',
                    'pos' => 4,
                ],
                20,
                20,
            ],
            [
                'UPDATE company_users SET a=1 WHERE working_count = 0',
                ['max_rows' => 15],
                20,
                20,
            ],
            [
                'SELECT * FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => 4,
                ],
                2,
                6,
                true,
            ],
            [
                'SELECT *, (SELECT COUNT(*) FROM tbl1) as c1, (SELECT 1 FROM tbl2) as c2 '
                . 'FROM company_users WHERE id != 0',
                [
                    'max_rows' => 250,
                    'pos' => 4,
                ],
                2,
                6,
                true,
            ],
            [

                'SELECT * FROM company_users',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                18,
                true,
            ],
            [
                'SELECT *, 1, (SELECT COUNT(*) FROM tbl1) as c1, '
                . '(SELECT 1 FROM tbl2) as c2 FROM company_users WHERE subquery_case = 0',
                [
                    'max_rows' => 10,
                    'pos' => 4,
                ],
                20,
                42,

            ],
            [
                'SELECT ( as c2 FROM company_users WHERE working_count = 0',// Invalid query
                ['max_rows' => 10],
                20,
                20,

            ],
            [
                'SELECT DISTINCT country_id FROM city;',
                ['max_rows' => 25, 'pos' => 0],
                25,
                109,
                false,
                'SELECT COUNT(*) FROM (SELECT DISTINCT country_id FROM city ) as cnt',
            ],
            [
                'SELECT * FROM t1 UNION SELECT * FROM t2;',
                ['max_rows' => -1, 'pos' => 0],
                25,
                109,
                false,
                'SELECT COUNT(*) FROM (SELECT * FROM t1 UNION SELECT * FROM t2 ) as cnt',
            ],
            [
                'SELECT SQL_NO_CACHE * FROM t1 WHERE id <> 0',
                ['max_rows' => -1, 'pos' => 0],
                25,
                100,
                false,
                'SELECT COUNT(*) FROM (SELECT 1 FROM t1 WHERE id <> 0 ) as cnt',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderCountQueryResults
     */
    public function testCountQueryResults(
        ?string $sqlQuery,
        array $sessionTmpVal,
        int $numRows,
        int $expectedNumRows,
        bool $justBrowsing = false,
        ?string $expectedCountQuery = null
    ): void {
        if ($justBrowsing) {
            $GLOBALS['cfg']['Server']['DisableIS'] = true;
        }

        $_SESSION['tmpval'] = $sessionTmpVal;

        $analyzed_sql_results = $sqlQuery === null ? [] : $this->parseAndAnalyze($sqlQuery);

        if ($expectedCountQuery !== null) {
            $this->dummyDbi->addResult(
                $expectedCountQuery,
                [[$expectedNumRows]],
                [],
                []
            );
        }

        $result = $this->callFunction(
            $this->sql,
            Sql::class,
            'countQueryResults',
            [
                $numRows,
                $justBrowsing,
                'my_dataset',// db
                'company_users',// table
                $analyzed_sql_results,
            ]
        );
        self::assertSame($expectedNumRows, $result);
        $this->assertAllQueriesConsumed();
    }

    public function testExecuteQueryAndSendQueryResponse(): void
    {
        $this->dummyDbi->addSelectDb('sakila');
        $this->dummyDbi->addResult(
            'SELECT * FROM `sakila`.`country` LIMIT 0, 3;',
            [
                ['1', 'Afghanistan', '2006-02-15 04:44:00'],
                ['2', 'Algeria', '2006-02-15 04:44:00'],
                ['3', 'American Samoa', '2006-02-15 04:44:00'],
            ],
            ['country_id', 'country', 'last_update'],
            [
                new FieldMetadata(MYSQLI_TYPE_SHORT, 0, (object) ['length' => 5]),
                new FieldMetadata(MYSQLI_TYPE_VAR_STRING, 0, (object) ['length' => 200]),
                new FieldMetadata(MYSQLI_TYPE_TIMESTAMP, 0, (object) ['length' => 19]),
            ]
        );
        $this->dummyDbi->addResult(
            'SHOW TABLE STATUS FROM `sakila` WHERE `Name` LIKE \'country%\'',
            [
                [
                    'country',
                    'InnoDB',
                    '10',
                    'Dynamic',
                    '109',
                    '150',
                    '16384',
                    '0',
                    '0',
                    '0',
                    '110',
                    '2011-12-13 14:15:16',
                    null,
                    null,
                    'utf8mb4_general_ci',
                    null,
                    '',
                    '',
                    '0',
                    'N',
                ],
            ],
            [
                'Name',
                'Engine',
                'Version',
                'Row_format',
                'Rows',
                'Avg_row_length',
                'Data_length',
                'Max_data_length',
                'Index_length',
                'Data_free',
                'Auto_increment',
                'Create_time',
                'Update_time',
                'Check_time',
                'Collation',
                'Checksum',
                'Create_options',
                'Comment',
                'Max_index_length',
                'Temporary',
            ]
        );
        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `sakila`.`country`',
            [
                [
                    'country',
                    'CREATE TABLE `country` (' . "\n"
                    . '  `country_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,' . "\n"
                    . '  `country` varchar(50) NOT NULL,' . "\n"
                    . '  `last_update` timestamp NOT NULL DEFAULT current_timestamp()'
                    . ' ON UPDATE current_timestamp(),' . "\n"
                    . '  PRIMARY KEY (`country_id`)' . "\n"
                    . ') ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4',
                ],
            ],
            ['Table', 'Create Table']
        );
        $this->dummyDbi->addResult('SELECT COUNT(*) FROM `sakila`.`country`', [['109']]);
        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `sakila`.`country`',
            [
                [
                    'country_id',
                    'smallint(5) unsigned',
                    null,
                    'NO',
                    'PRI',
                    null,
                    'auto_increment',
                    'select,insert,update,references',
                    '',
                ],
                [
                    'country',
                    'varchar(50)',
                    'utf8mb4_general_ci',
                    'NO',
                    '',
                    null,
                    '',
                    'select,insert,update,references',
                    '',
                ],
                [
                    'last_update',
                    'timestamp',
                    null,
                    'NO',
                    '',
                    'current_timestamp()',
                    'on update current_timestamp()',
                    'select,insert,update,references',
                    '',
                ],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment']
        );
        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `sakila`.`country`',
            [
                ['country_id', 'smallint(5) unsigned', 'NO', 'PRI', null, 'auto_increment'],
                ['country', 'varchar(50)', 'NO', '', null, ''],
                ['last_update', 'timestamp', 'NO', '', 'current_timestamp()', 'on update current_timestamp()'],
            ],
            ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra']
        );
        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `sakila`.`country`',
            [['country', '0', 'PRIMARY', 'country_id']],
            ['Table', 'Non_unique', 'Key_name', 'Column_name']
        );
        $this->dummyDbi->addResult(
            'SELECT TABLE_NAME FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'sakila\' AND TABLE_NAME = \'country\' AND IS_UPDATABLE = \'YES\'',
            []
        );
        $_SESSION['sql_from_query_box'] = true;
        $GLOBALS['db'] = 'sakila';
        $GLOBALS['table'] = 'country';
        $GLOBALS['sql_query'] = 'SELECT * FROM `sakila`.`country` LIMIT 0, 3;';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $actual = $this->sql->executeQueryAndSendQueryResponse(
            null,
            false,
            'sakila',
            'different_table',
            null,
            null,
            null,
            null,
            null,
            'index.php?route=/sql',
            null,
            null,
            'SELECT * FROM `sakila`.`country` LIMIT 0, 3;',
            null
        );
        self::assertStringContainsString('Showing rows 0 -  2 (3 total', $actual);
        self::assertStringContainsString('SELECT * FROM `sakila`.`country` LIMIT 0, 3;', $actual);
        self::assertStringContainsString('Afghanistan', $actual);
        self::assertStringContainsString('Algeria', $actual);
        self::assertStringContainsString('American Samoa', $actual);
        self::assertStringContainsString('data-type="int"', $actual);
        self::assertStringContainsString('data-type="string"', $actual);
        self::assertStringContainsString('data-type="timestamp"', $actual);
    }

    public function testGetDetailedProfilingStatsWithoutData(): void
    {
        $method = new ReflectionMethod($this->sql, 'getDetailedProfilingStats');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        self::assertSame(
            ['total_time' => 0, 'states' => [], 'chart' => [], 'profile' => []],
            $method->invoke($this->sql, [])
        );
    }

    public function testGetDetailedProfilingStatsWithData(): void
    {
        $method = new ReflectionMethod($this->sql, 'getDetailedProfilingStats');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $profiling = [
            ['Status' => 'Starting', 'Duration' => '0.000017'],
            ['Status' => 'checking permissions', 'Duration' => '0.000003'],
            ['Status' => 'Opening tables', 'Duration' => '0.000152'],
            ['Status' => 'After opening tables', 'Duration' => '0.000004'],
            ['Status' => 'System lock', 'Duration' => '0.000002'],
            ['Status' => 'table lock', 'Duration' => '0.000003'],
            ['Status' => 'Opening tables', 'Duration' => '0.000008'],
            ['Status' => 'After opening tables', 'Duration' => '0.000002'],
            ['Status' => 'System lock', 'Duration' => '0.000002'],
            ['Status' => 'table lock', 'Duration' => '0.000012'],
            ['Status' => 'Unlocking tables', 'Duration' => '0.000003'],
            ['Status' => 'closing tables', 'Duration' => '0.000005'],
            ['Status' => 'init', 'Duration' => '0.000007'],
            ['Status' => 'Optimizing', 'Duration' => '0.000004'],
            ['Status' => 'Statistics', 'Duration' => '0.000006'],
            ['Status' => 'Preparing', 'Duration' => '0.000006'],
            ['Status' => 'Executing', 'Duration' => '0.000002'],
            ['Status' => 'Sending data', 'Duration' => '0.000029'],
            ['Status' => 'End of update loop', 'Duration' => '0.000003'],
            ['Status' => 'Query end', 'Duration' => '0.000002'],
            ['Status' => 'Commit', 'Duration' => '0.000002'],
            ['Status' => 'closing tables', 'Duration' => '0.000002'],
            ['Status' => 'Unlocking tables', 'Duration' => '0.000001'],
            ['Status' => 'closing tables', 'Duration' => '0.000002'],
            ['Status' => 'Starting cleanup', 'Duration' => '0.000002'],
            ['Status' => 'Freeing items', 'Duration' => '0.000002'],
            ['Status' => 'Updating status', 'Duration' => '0.000007'],
            ['Status' => 'Reset for next command', 'Duration' => '0.000009'],
        ];
        $expected = [
            'total_time' => 0.000299,
            'states' => [
                'Starting' => ['total_time' => '0.000017', 'calls' => 1],
                'Checking Permissions' => ['total_time' => '0.000003', 'calls' => 1],
                'Opening Tables' => ['total_time' => 0.00016, 'calls' => 2],
                'After Opening Tables' => ['total_time' => 6.0E-6, 'calls' => 2],
                'System Lock' => ['total_time' => 4.0E-6, 'calls' => 2],
                'Table Lock' => ['total_time' => 1.5E-5, 'calls' => 2],
                'Unlocking Tables' => ['total_time' => 4.0E-6, 'calls' => 2],
                'Closing Tables' => ['total_time' => 9.0E-6, 'calls' => 3],
                'Init' => ['total_time' => '0.000007', 'calls' => 1],
                'Optimizing' => ['total_time' => '0.000004', 'calls' => 1],
                'Statistics' => ['total_time' => '0.000006', 'calls' => 1],
                'Preparing' => ['total_time' => '0.000006', 'calls' => 1],
                'Executing' => ['total_time' => '0.000002', 'calls' => 1],
                'Sending Data' => ['total_time' => '0.000029', 'calls' => 1],
                'End Of Update Loop' => ['total_time' => '0.000003', 'calls' => 1],
                'Query End' => ['total_time' => '0.000002', 'calls' => 1],
                'Commit' => ['total_time' => '0.000002', 'calls' => 1],
                'Starting Cleanup' => ['total_time' => '0.000002', 'calls' => 1],
                'Freeing Items' => ['total_time' => '0.000002', 'calls' => 1],
                'Updating Status' => ['total_time' => '0.000007', 'calls' => 1],
                'Reset For Next Command' => ['total_time' => '0.000009', 'calls' => 1],
            ],
            'chart' => [
                'Starting' => '0.000017',
                'Checking Permissions' => '0.000003',
                'Opening Tables' => 0.00016,
                'After Opening Tables' => 6.0E-6,
                'System Lock' => 4.0E-6,
                'Table Lock' => 1.5E-5,
                'Unlocking Tables' => 4.0E-6,
                'Closing Tables' => 9.0E-6,
                'Init' => '0.000007',
                'Optimizing' => '0.000004',
                'Statistics' => '0.000006',
                'Preparing' => '0.000006',
                'Executing' => '0.000002',
                'Sending Data' => '0.000029',
                'End Of Update Loop' => '0.000003',
                'Query End' => '0.000002',
                'Commit' => '0.000002',
                'Starting Cleanup' => '0.000002',
                'Freeing Items' => '0.000002',
                'Updating Status' => '0.000007',
                'Reset For Next Command' => '0.000009',
            ],
            'profile' => [
                ['status' => 'Starting', 'duration' => '17 µ', 'duration_raw' => '0.000017'],
                ['status' => 'Checking Permissions', 'duration' => '3 µ', 'duration_raw' => '0.000003'],
                ['status' => 'Opening Tables', 'duration' => '152 µ', 'duration_raw' => '0.000152'],
                ['status' => 'After Opening Tables', 'duration' => '4 µ', 'duration_raw' => '0.000004'],
                ['status' => 'System Lock', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Table Lock', 'duration' => '3 µ', 'duration_raw' => '0.000003'],
                ['status' => 'Opening Tables', 'duration' => '8 µ', 'duration_raw' => '0.000008'],
                ['status' => 'After Opening Tables', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'System Lock', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Table Lock', 'duration' => '12 µ', 'duration_raw' => '0.000012'],
                ['status' => 'Unlocking Tables', 'duration' => '3 µ', 'duration_raw' => '0.000003'],
                ['status' => 'Closing Tables', 'duration' => '5 µ', 'duration_raw' => '0.000005'],
                ['status' => 'Init', 'duration' => '7 µ', 'duration_raw' => '0.000007'],
                ['status' => 'Optimizing', 'duration' => '4 µ', 'duration_raw' => '0.000004'],
                ['status' => 'Statistics', 'duration' => '6 µ', 'duration_raw' => '0.000006'],
                ['status' => 'Preparing', 'duration' => '6 µ', 'duration_raw' => '0.000006'],
                ['status' => 'Executing', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Sending Data', 'duration' => '29 µ', 'duration_raw' => '0.000029'],
                ['status' => 'End Of Update Loop', 'duration' => '3 µ', 'duration_raw' => '0.000003'],
                ['status' => 'Query End', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Commit', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Closing Tables', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Unlocking Tables', 'duration' => '1 µ', 'duration_raw' => '0.000001'],
                ['status' => 'Closing Tables', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Starting Cleanup', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Freeing Items', 'duration' => '2 µ', 'duration_raw' => '0.000002'],
                ['status' => 'Updating Status', 'duration' => '7 µ', 'duration_raw' => '0.000007'],
                ['status' => 'Reset For Next Command', 'duration' => '9 µ', 'duration_raw' => '0.000009'],
            ],
        ];
        self::assertSame($expected, $method->invoke($this->sql, $profiling));
    }
}
