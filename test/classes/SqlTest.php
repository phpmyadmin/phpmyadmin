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
use stdClass;

use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_VAR_STRING;

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
        $this->assertEquals(
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

        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                $this->parseAndAnalyze('SELECT * FROM tbl'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                $this->parseAndAnalyze('SELECT col FROM tbl'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                $this->parseAndAnalyze('SELECT 1'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                $this->parseAndAnalyze('SELECT col1, col2 FROM tbl'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                $this->parseAndAnalyze('SELECT COUNT(*) from tbl'),
            ])
        );
    }

    /**
     * Test for isAppendLimitClause
     */
    public function testIsAppendLimitClause(): void
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
                $this->parseAndAnalyze('SELECT * FROM tbl'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
                $this->parseAndAnalyze('SELECT * from tbl LIMIT 0, 10'),
            ])
        );
    }

    public function testIsJustBrowsing(): void
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        $this->assertTrue(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * FROM db.tbl'),
            null
        ));

        $this->assertTrue(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * FROM tbl WHERE 1'),
            null
        ));

        $this->assertFalse(Sql::isJustBrowsing(
            $this->parseAndAnalyze('SELECT * from tbl1, tbl2 LIMIT 0, 10'),
            null
        ));
    }

    /**
     * Test for isDeleteTransformationInfo
     */
    public function testIsDeleteTransformationInfo(): void
    {
        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                $this->parseAndAnalyze('ALTER TABLE tbl DROP COLUMN col'),
            ])
        );

        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                $this->parseAndAnalyze('DROP TABLE tbl'),
            ])
        );

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                $this->parseAndAnalyze('SELECT * from tbl'),
            ])
        );
    }

    /**
     * Test for hasNoRightsToDropDatabase
     */
    public function testHasNoRightsToDropDatabase(): void
    {
        $this->assertTrue(
            $this->sql->hasNoRightsToDropDatabase(
                $this->parseAndAnalyze('DROP DATABASE db'),
                false,
                false
            )
        );

        $this->assertFalse(
            $this->sql->hasNoRightsToDropDatabase(
                $this->parseAndAnalyze('DROP TABLE tbl'),
                false,
                false
            )
        );

        $this->assertFalse(
            $this->sql->hasNoRightsToDropDatabase(
                $this->parseAndAnalyze('SELECT * from tbl'),
                false,
                false
            )
        );
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
        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );

        // should not matter on where the odd column occurs
        $fields_meta = [
            $col2,
            $col3,
            $col1,
        ];
        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );

        $fields_meta = [
            $col3,
            $col1,
            $col2,
        ];
        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );
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

        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );
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
        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );

        // should not matter on where the function column occurs
        $fields_meta = [
            $col2,
            $col3,
            $col1,
        ];
        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );

        $fields_meta = [
            $col3,
            $col1,
            $col2,
        ];
        $this->assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );
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

        $this->assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fields_meta])
        );
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

    public function dataProviderCountQueryResults(): array
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
                'SELECT COUNT(*) FROM (select * from game_auth_logs l join ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) last_log on last_log.id = l.id'
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
                'SELECT COUNT(*) FROM (select l.* from game_auth_logs l join ('
                    . ' select al.user_id, max(al.id) as id from game_auth_logs al '
                    . 'where al.successfull = 1 group by al.user_id ) last_log on last_log.id = l.id'
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
        $this->assertSame($expectedNumRows, $result);
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
        $this->assertStringContainsString('Showing rows 0 -  2 (3 total', $actual);
        $this->assertStringContainsString('SELECT * FROM `sakila`.`country` LIMIT 0, 3;', $actual);
        $this->assertStringContainsString('Afghanistan', $actual);
        $this->assertStringContainsString('Algeria', $actual);
        $this->assertStringContainsString('American Samoa', $actual);
        $this->assertStringContainsString('data-type="int"', $actual);
        $this->assertStringContainsString('data-type="string"', $actual);
        $this->assertStringContainsString('data-type="timestamp"', $actual);
    }
}
