<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Sql
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Sql;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

/**
 * Tests for PhpMyAdmin\Sql
 *
 * @package PhpMyAdmin-test
 */
class SqlTest extends TestCase
{
    /**
     * @var Sql
     */
    private $sql;

    /**
     * Setup for test cases
     *
     * @return void
     */
    protected function setUp()
    {
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
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $this->sql = new Sql();
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return mixed the output from the protected method.
     */
    private function callProtectedMethod($name, array $params = [])
    {
        $method = new ReflectionMethod(Sql::class, $name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->sql, $params);
    }

    /**
     * Test for getSqlWithLimitClause
     *
     * @return void
     */
    public function testGetSqlWithLimitClause()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['pos'] = 1;
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 2;

        $analyzed_sql_results = $this->sql->parseAndAnalyze(
            'SELECT * FROM test LIMIT 0, 10'
        );
        $this->assertEquals(
            'SELECT * FROM test LIMIT 1, 2 ',
            $this->callProtectedMethod('getSqlWithLimitClause', [&$analyzed_sql_results])
        );
    }

    /**
     * Test for isRememberSortingOrder
     *
     * @return void
     */
    public function testIsRememberSortingOrder()
    {
        // Test environment.
        $GLOBALS['cfg']['RememberSorting'] = true;

        $this->assertTrue(
            $this->callProtectedMethod('isRememberSortingOrder', [
                $this->sql->parseAndAnalyze('SELECT * FROM tbl')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isRememberSortingOrder', [
                $this->sql->parseAndAnalyze('SELECT col FROM tbl')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isRememberSortingOrder', [
                $this->sql->parseAndAnalyze('SELECT 1')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isRememberSortingOrder', [
                $this->sql->parseAndAnalyze('SELECT col1, col2 FROM tbl')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isRememberSortingOrder', [
                $this->sql->parseAndAnalyze('SELECT COUNT(*) from tbl')
            ])
        );
    }

    /**
     * Test for isAppendLimitClause
     *
     * @return void
     */
    public function testIsAppendLimitClause()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        $this->assertTrue(
            $this->callProtectedMethod('isAppendLimitClause', [
                $this->sql->parseAndAnalyze('SELECT * FROM tbl')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isAppendLimitClause', [
                $this->sql->parseAndAnalyze('SELECT * from tbl LIMIT 0, 10')
            ])
        );
    }

    /**
     * Test for isJustBrowsing
     *
     * @return void
     */
    public function testIsJustBrowsing()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;

        $this->assertTrue(
            $this->sql->isJustBrowsing(
                $this->sql->parseAndAnalyze('SELECT * FROM db.tbl'),
                null
            )
        );

        $this->assertTrue(
            $this->sql->isJustBrowsing(
                $this->sql->parseAndAnalyze('SELECT * FROM tbl WHERE 1'),
                null
            )
        );

        $this->assertFalse(
            $this->sql->isJustBrowsing(
                $this->sql->parseAndAnalyze('SELECT * from tbl1, tbl2 LIMIT 0, 10'),
                null
            )
        );
    }

    /**
     * Test for isDeleteTransformationInfo
     *
     * @return void
     */
    public function testIsDeleteTransformationInfo()
    {
        $this->assertTrue(
            $this->callProtectedMethod('isDeleteTransformationInfo', [
                $this->sql->parseAndAnalyze('ALTER TABLE tbl DROP COLUMN col')
            ])
        );

        $this->assertTrue(
            $this->callProtectedMethod('isDeleteTransformationInfo', [
                $this->sql->parseAndAnalyze('DROP TABLE tbl')
            ])
        );

        $this->assertFalse(
            $this->callProtectedMethod('isDeleteTransformationInfo', [
                $this->sql->parseAndAnalyze('SELECT * from tbl')
            ])
        );
    }

    /**
     * Test for hasNoRightsToDropDatabase
     *
     * @return void
     */
    public function testHasNoRightsToDropDatabase()
    {
        $this->assertEquals(
            true,
            $this->sql->hasNoRightsToDropDatabase(
                $this->sql->parseAndAnalyze('DROP DATABASE db'),
                false,
                false
            )
        );

        $this->assertEquals(
            false,
            $this->sql->hasNoRightsToDropDatabase(
                $this->sql->parseAndAnalyze('DROP TABLE tbl'),
                false,
                false
            )
        );

        $this->assertEquals(
            false,
            $this->sql->hasNoRightsToDropDatabase(
                $this->sql->parseAndAnalyze('SELECT * from tbl'),
                false,
                false
            )
        );
    }

    /**
     * Should return false if all columns are not from the same table
     *
     * @return void
     */
    public function testWithMultipleTables()
    {
        $col1 = new stdClass;
        $col1->table = 'table1';
        $col2 = new stdClass;
        $col2->table = 'table1';
        $col3 = new stdClass;
        $col3->table = 'table3';

        $fields_meta = array($col1, $col2, $col3);
        $this->assertFalse(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );

        // should not matter on where the odd column occurs
        $fields_meta = array($col2, $col3, $col1);
        $this->assertFalse(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );

        $fields_meta = array($col3, $col1, $col2);
        $this->assertFalse(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );
    }

    /**
     * Should return true if all the columns are from the same table
     *
     * @return void
     */
    public function testWithSameTable()
    {
        $col1 = new stdClass;
        $col1->table = 'table1';
        $col2 = new stdClass;
        $col2->table = 'table1';
        $col3 = new stdClass;
        $col3->table = 'table1';
        $fields_meta = array($col1, $col2, $col3);

        $this->assertTrue(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );
    }

    /**
     * Should return true even if function columns (table is '') occur when others
     * are from the same table.
     *
     * @return void
     */
    public function testWithFunctionColumns()
    {
        $col1 = new stdClass;
        $col1->table = 'table1';
        $col2 = new stdClass;
        $col2->table = '';
        $col3 = new stdClass;
        $col3->table = 'table1';

        $fields_meta = array($col1, $col2, $col3);
        $this->assertTrue(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );

        // should not matter on where the function column occurs
        $fields_meta = array($col2, $col3, $col1);
        $this->assertTrue(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );

        $fields_meta = array($col3, $col1, $col2);
        $this->assertTrue(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );
    }

    /**
     * We can not say all the columns are from the same table if all the columns
     * are funtion columns (table is '')
     *
     * @return void
     */
    public function testWithOnlyFunctionColumns()
    {
        $col1 = new stdClass;
        $col1->table = '';
        $col2 = new stdClass;
        $col2->table = '';
        $col3 = new stdClass;
        $col3->table = '';
        $fields_meta = array($col1, $col2, $col3);

        $this->assertFalse(
            $this->callProtectedMethod('resultSetHasJustOneTable', [$fields_meta])
        );
    }
}
