<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use stdClass;

use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_VAR_STRING;

#[CoversClass(Sql::class)]
class SqlTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Sql $sql;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        Current::$database = 'db';
        Current::$table = 'table';

        $relation = new Relation($this->dbi);
        $this->sql = new Sql(
            $this->dbi,
            $relation,
            new RelationCleanup($this->dbi, $relation),
            new Transformations($this->dbi, $relation),
            new Template(),
            new BookmarkRepository($this->dbi, $relation),
            Config::getInstance(),
        );
    }

    /**
     * Test for getSqlWithLimitClause
     */
    public function testGetSqlWithLimitClause(): void
    {
        // Test environment.
        $_SESSION['tmpval']['pos'] = 1;
        $_SESSION['tmpval']['max_rows'] = 2;

        self::assertSame('SELECT * FROM test LIMIT 1, 2', $this->callFunction(
            $this->sql,
            Sql::class,
            'getSqlWithLimitClause',
            [ParseAnalyze::sqlQuery('SELECT * FROM test LIMIT 0, 10', Current::$database)[0]],
        ));
    }

    /**
     * Test for isRememberSortingOrder
     */
    public function testIsRememberSortingOrder(): void
    {
        // Test environment.
        Config::getInstance()->settings['RememberSorting'] = true;

        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                ParseAnalyze::sqlQuery('SELECT * FROM tbl', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                ParseAnalyze::sqlQuery('SELECT col FROM tbl', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                ParseAnalyze::sqlQuery('SELECT 1', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                ParseAnalyze::sqlQuery('SELECT col1, col2 FROM tbl', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isRememberSortingOrder', [
                ParseAnalyze::sqlQuery('SELECT COUNT(*) from tbl', Current::$database)[0],
            ]),
        );
    }

    /**
     * Test for isAppendLimitClause
     */
    public function testIsAppendLimitClause(): void
    {
        // Test environment.
        $_SESSION['tmpval']['max_rows'] = 10;

        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
                ParseAnalyze::sqlQuery('SELECT * FROM tbl', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isAppendLimitClause', [
                ParseAnalyze::sqlQuery('SELECT * from tbl LIMIT 0, 10', Current::$database)[0],
            ]),
        );
    }

    public function testIsJustBrowsing(): void
    {
        // Test environment.
        $_SESSION['tmpval']['max_rows'] = 10;

        self::assertTrue(Sql::isJustBrowsing(
            ParseAnalyze::sqlQuery('SELECT * FROM db.tbl', Current::$database)[0],
        ));

        self::assertTrue(Sql::isJustBrowsing(
            ParseAnalyze::sqlQuery('SELECT * FROM tbl WHERE 1', Current::$database)[0],
        ));

        self::assertFalse(Sql::isJustBrowsing(
            ParseAnalyze::sqlQuery('SELECT * from tbl1, tbl2 LIMIT 0, 10', Current::$database)[0],
        ));
    }

    /**
     * Test for isDeleteTransformationInfo
     */
    public function testIsDeleteTransformationInfo(): void
    {
        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                ParseAnalyze::sqlQuery('ALTER TABLE tbl DROP COLUMN col', Current::$database)[0],
            ]),
        );

        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                ParseAnalyze::sqlQuery('DROP TABLE tbl', Current::$database)[0],
            ]),
        );

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'isDeleteTransformationInfo', [
                ParseAnalyze::sqlQuery('SELECT * from tbl', Current::$database)[0],
            ]),
        );
    }

    /**
     * Test for hasNoRightsToDropDatabase
     */
    public function testHasNoRightsToDropDatabase(): void
    {
        $this->dummyDbi->addResult(
            'SELECT 1 FROM mysql.user LIMIT 1',
            [],
        );

        self::assertTrue(
            $this->sql->hasNoRightsToDropDatabase(
                ParseAnalyze::sqlQuery('DROP DATABASE db', Current::$database)[0],
            ),
        );

        self::assertFalse(
            $this->sql->hasNoRightsToDropDatabase(
                ParseAnalyze::sqlQuery('DROP TABLE tbl', Current::$database)[0],
            ),
        );

        self::assertFalse(
            $this->sql->hasNoRightsToDropDatabase(
                ParseAnalyze::sqlQuery('SELECT * from tbl', Current::$database)[0],
            ),
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

        $fieldsMeta = [$col1, $col2, $col3];
        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
        );

        // should not matter on where the odd column occurs
        $fieldsMeta = [$col2, $col3, $col1];
        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
        );

        $fieldsMeta = [$col3, $col1, $col2];
        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
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
        $fieldsMeta = [$col1, $col2, $col3];

        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
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

        $fieldsMeta = [$col1, $col2, $col3];
        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
        );

        // should not matter on where the function column occurs
        $fieldsMeta = [$col2, $col3, $col1];
        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
        );

        $fieldsMeta = [$col3, $col1, $col2];
        self::assertTrue(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
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
        $fieldsMeta = [$col1, $col2, $col3];

        self::assertFalse(
            $this->callFunction($this->sql, Sql::class, 'resultSetHasJustOneTable', [$fieldsMeta]),
        );
    }

    /** @return mixed[][] */
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
            ['SELECT * FROM company_users WHERE id != 0 LIMIT 0, 10', ['max_rows' => 250], -1, -1],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 250, 'pos' => -1], -1, -2],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 250, 'pos' => -1], -1, -2],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 250, 'pos' => 250], -1, 249],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 250, 'pos' => 4], 2, 6],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 'all', 'pos' => 4], 2, 2],
            [null, [], 2, 0],
            ['SELECT * FROM company_users LIMIT 1,4', ['max_rows' => 10, 'pos' => 4], 20, 20],
            ['SELECT * FROM company_users', ['max_rows' => 10, 'pos' => 4], 20, 4],
            ['SELECT * FROM company_users WHERE not_working_count != 0', ['max_rows' => 10, 'pos' => 4], 20, 0],
            ['SELECT * FROM company_users WHERE working_count = 0', ['max_rows' => 10, 'pos' => 4], 20, 15],
            ['UPDATE company_users SET a=1 WHERE working_count = 0', ['max_rows' => 10, 'pos' => 4], 20, 20],
            ['UPDATE company_users SET a=1 WHERE working_count = 0', ['max_rows' => 'all', 'pos' => 4], 20, 20],
            ['UPDATE company_users SET a=1 WHERE working_count = 0', ['max_rows' => 15], 20, 20],
            ['SELECT * FROM company_users WHERE id != 0', ['max_rows' => 250, 'pos' => 4], 2, 6, true],
            [
                'SELECT *, (SELECT COUNT(*) FROM tbl1) as c1, (SELECT 1 FROM tbl2) as c2 '
                . 'FROM company_users WHERE id != 0',
                ['max_rows' => 250, 'pos' => 4],
                2,
                6,
                true,
            ],
            ['SELECT * FROM company_users', ['max_rows' => 10, 'pos' => 4], 20, 18, true],
            [
                'SELECT *, 1, (SELECT COUNT(*) FROM tbl1) as c1, '
                . '(SELECT 1 FROM tbl2) as c2 FROM company_users WHERE subquery_case = 0',
                ['max_rows' => 10, 'pos' => 4],
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

    /** @param array<string, int|string> $sessionTmpVal */
    #[DataProvider('dataProviderCountQueryResults')]
    public function testCountQueryResults(
        string|null $sqlQuery,
        array $sessionTmpVal,
        int $numRows,
        int $expectedNumRows,
        bool $justBrowsing = false,
        string|null $expectedCountQuery = null,
    ): void {
        if ($justBrowsing) {
            Config::getInstance()->selectedServer['DisableIS'] = true;
        }

        $_SESSION['tmpval'] = $sessionTmpVal;

        if ($expectedCountQuery !== null) {
            $this->dummyDbi->addResult(
                $expectedCountQuery,
                [[$expectedNumRows]],
                [],
                [],
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
                ParseAnalyze::sqlQuery($sqlQuery ?? '', Current::$database)[0],
            ],
        );
        self::assertSame($expectedNumRows, $result);
        $this->dummyDbi->assertAllQueriesConsumed();
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
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_SHORT, 'length' => 5]),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_VAR_STRING, 'length' => 200]),
                FieldHelper::fromArray(['type' => MYSQLI_TYPE_TIMESTAMP, 'length' => 19]),
            ],
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
            ],
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
            ['Table', 'Create Table'],
        );
        $this->dummyDbi->addResult('SELECT COUNT(*) FROM `sakila`.`country`', [['109']]);
        $this->dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'sakila\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'country\''
                . ' ORDER BY `ORDINAL_POSITION`',
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
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $this->dummyDbi->addResult(
            'SELECT `COLUMN_NAME` AS `Field`, `COLUMN_TYPE` AS `Type`, `COLLATION_NAME` AS `Collation`,'
                . ' `IS_NULLABLE` AS `Null`, `COLUMN_KEY` AS `Key`,'
                . ' `COLUMN_DEFAULT` AS `Default`, `EXTRA` AS `Extra`, `PRIVILEGES` AS `Privileges`,'
                . ' `COLUMN_COMMENT` AS `Comment`'
                . ' FROM `information_schema`.`COLUMNS`'
                . ' WHERE `TABLE_SCHEMA` COLLATE utf8_bin = \'sakila\' AND'
                . ' `TABLE_NAME` COLLATE utf8_bin = \'country\''
                . ' ORDER BY `ORDINAL_POSITION`',
            [
                ['country_id', 'smallint(5) unsigned', null, 'NO', 'PRI', null, 'auto_increment', '', ''],
                ['country', 'varchar(50)', null, 'NO', '', null, '', '', ''],
                [
                    'last_update',
                    'timestamp',
                    null,
                    'NO',
                    '',
                    'current_timestamp()',
                    'on update current_timestamp()',
                    '',
                    '',
                ],
            ],
            ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment'],
        );
        $this->dummyDbi->addResult(
            'SHOW INDEXES FROM `sakila`.`country`',
            [['country', '0', 'PRIMARY', 'country_id']],
            ['Table', 'Non_unique', 'Key_name', 'Column_name'],
        );
        $this->dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'sakila\' AND TABLE_NAME = \'country\' AND IS_UPDATABLE = \'YES\'',
            [],
        );
        $_SESSION['sql_from_query_box'] = true;
        Current::$database = 'sakila';
        Current::$table = 'country';
        Current::$sqlQuery = 'SELECT * FROM `sakila`.`country` LIMIT 0, 3;';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->selectedServer['user'] = 'user';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com');

        $actual = $this->sql->executeQueryAndSendQueryResponse(
            $request,
            null,
            false,
            'sakila',
            'different_table',
            '',
            '',
            'index.php?route=/sql',
            null,
            '',
            'SELECT * FROM `sakila`.`country` LIMIT 0, 3;',
            'SELECT * FROM `sakila`.`country` LIMIT 0, 3;',
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
        self::assertSame([], $method->invoke($this->sql, []));
    }

    public function testGetDetailedProfilingStatsWithZeroTotalTime(): void
    {
        $method = new ReflectionMethod($this->sql, 'getDetailedProfilingStats');
        $profiling = [
            ['Status' => 'Starting', 'Duration' => '0'],
            ['Status' => 'checking permissions', 'Duration' => '0'],
        ];
        self::assertSame([], $method->invoke($this->sql, $profiling));
    }

    public function testGetDetailedProfilingStatsWithData(): void
    {
        $method = new ReflectionMethod($this->sql, 'getDetailedProfilingStats');
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
                'Opening Tables' => ['total_time' => 0.00016, 'calls' => 2],
                'Sending Data' => ['total_time' => 0.000029, 'calls' => 1],
                'Starting' => ['total_time' => 0.000017, 'calls' => 1],
                'Table Lock' => ['total_time' => 0.000015, 'calls' => 2],
                'Closing Tables' => ['total_time' => 0.000009, 'calls' => 3],
                'Reset For Next Command' => ['total_time' => 0.000009, 'calls' => 1],
                'Init' => ['total_time' => 0.000007, 'calls' => 1],
                'Updating Status' => ['total_time' => 0.000007, 'calls' => 1],
                'After Opening Tables' => ['total_time' => 0.000006, 'calls' => 2],
                'Statistics' => ['total_time' => 0.000006, 'calls' => 1],
                'Preparing' => ['total_time' => 0.000006, 'calls' => 1],
                'System Lock' => ['total_time' => 0.000004, 'calls' => 2],
                'Unlocking Tables' => ['total_time' => 0.000004, 'calls' => 2],
                'Optimizing' => ['total_time' => 0.000004, 'calls' => 1],
                'Checking Permissions' => ['total_time' => 0.000003, 'calls' => 1],
                'End Of Update Loop' => ['total_time' => 0.000003, 'calls' => 1],
                'Executing' => ['total_time' => 0.000002, 'calls' => 1],
                'Query End' => ['total_time' => 0.000002, 'calls' => 1],
                'Commit' => ['total_time' => 0.000002, 'calls' => 1],
                'Starting Cleanup' => ['total_time' => 0.000002, 'calls' => 1],
                'Freeing Items' => ['total_time' => 0.000002, 'calls' => 1],
            ],
            'chart' => [
                'labels' => [
                    'Opening Tables',
                    'Sending Data',
                    'Starting',
                    'Table Lock',
                    'Closing Tables',
                    'Reset For Next Command',
                    'Init',
                    'Updating Status',
                    'After Opening Tables',
                    'Statistics',
                    'Preparing',
                    'System Lock',
                    'Unlocking Tables',
                    'Optimizing',
                    'Checking Permissions',
                    'End Of Update Loop',
                    'Executing',
                    'Query End',
                    'Commit',
                    'Starting Cleanup',
                    'Freeing Items',
                ],
                'data' => [
                    0.00016,
                    0.000029,
                    0.000017,
                    0.000015,
                    0.000009,
                    0.000009,
                    0.000007,
                    0.000007,
                    0.000006,
                    0.000006,
                    0.000006,
                    0.000004,
                    0.000004,
                    0.000004,
                    0.000003,
                    0.000003,
                    0.000002,
                    0.000002,
                    0.000002,
                    0.000002,
                    0.000002,
                ],
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
