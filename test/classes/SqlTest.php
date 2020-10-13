<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Operations;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use stdClass;

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
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::loadDefaultConfig();
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
     * are funtion columns (table is '')
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
}
