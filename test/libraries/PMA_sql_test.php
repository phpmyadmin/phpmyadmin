<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/sql.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sql.lib.php';

/**
 * Tests for libraries/sql.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SqlTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test PMA_getSqlWithLimitClause
     *
     * @return void
     */
    public function testGetSqlWithLimitClause()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['pos'] = 1;
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 2;
        $GLOBALS['db'] = 'db';

        $analyzed_sql_results = PMA_parseAndAnalyze(
            'SELECT * FROM test LIMIT 0, 10'
        );
        $this->assertEquals(
            'SELECT * FROM test LIMIT 1, 2 ',
            PMA_getSqlWithLimitClause($analyzed_sql_results)
        );
    }

    /**
     * Test PMA_isRememberSortingOrder
     *
     * @return void
     */
    public function testIsRememberSortingOrder()
    {
        // Test environment.
        $GLOBALS['cfg']['RememberSorting'] = true;
        $GLOBALS['db'] = 'db';

        $this->assertTrue(
            PMA_isRememberSortingOrder(
                PMA_parseAndAnalyze('SELECT * FROM tbl')
            )
        );

        $this->assertFalse(
            PMA_isRememberSortingOrder(
                PMA_parseAndAnalyze('SELECT col FROM tbl')
            )
        );

        $this->assertFalse(
            PMA_isRememberSortingOrder(
                PMA_parseAndAnalyze('SELECT 1')
            )
        );

        $this->assertFalse(
            PMA_isRememberSortingOrder(
                PMA_parseAndAnalyze('SELECT col1, col2 FROM tbl')
            )
        );

        $this->assertFalse(
            PMA_isRememberSortingOrder(
                PMA_parseAndAnalyze('SELECT COUNT(*) from tbl')
            )
        );
    }

    /**
     * Test PMA_isAppendLimitClause
     *
     * @return void
     */
    public function testIsAppendLimitClause()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;
        $GLOBALS['db'] = 'db';

        $this->assertTrue(
            PMA_isAppendLimitClause(
                PMA_parseAndAnalyze('SELECT * FROM tbl')
            )
        );

        $this->assertFalse(
            PMA_isAppendLimitClause(
                PMA_parseAndAnalyze('SELECT * from tbl LIMIT 0, 10')
            )
        );
    }

    /**
     * Test PMA_isJustBrowsing
     *
     * @return void
     */
    public function testIsJustBrowsing()
    {
        // Test environment.
        $GLOBALS['_SESSION']['tmpval']['max_rows'] = 10;
        $GLOBALS['db'] = 'db';

        $this->assertTrue(
            PMA_isJustBrowsing(
                PMA_parseAndAnalyze('SELECT * FROM db.tbl'),
                null
            )
        );

        $this->assertTrue(
            PMA_isJustBrowsing(
                PMA_parseAndAnalyze('SELECT * FROM tbl WHERE 1'),
                null
            )
        );

        $this->assertFalse(
            PMA_isJustBrowsing(
                PMA_parseAndAnalyze('SELECT * from tbl1, tbl2 LIMIT 0, 10'),
                null
            )
        );
    }

    /**
     * Test PMA_isDeleteTransformationInfo
     *
     * @return void
     */
    public function testIsDeleteTransformationInfo()
    {
        $this->assertTrue(
            PMA_isDeleteTransformationInfo(
                PMA_parseAndAnalyze('ALTER TABLE tbl DROP COLUMN col')
            )
        );

        $this->assertTrue(
            PMA_isDeleteTransformationInfo(
                PMA_parseAndAnalyze('DROP TABLE tbl')
            )
        );

        $this->assertFalse(
            PMA_isDeleteTransformationInfo(
                PMA_parseAndAnalyze('SELECT * from tbl')
            )
        );
    }

    /**
     * Test PMA_hasNoRightsToDropDatabase
     *
     * @return void
     */
    public function testHasNoRightsToDropDatabase()
    {
        $this->assertEquals(
            !defined('PMA_CHK_DROP'),
            PMA_hasNoRightsToDropDatabase(
                PMA_parseAndAnalyze('DROP DATABASE db'),
                false,
                false
            )
        );

        $this->assertEquals(
            !defined('PMA_CHK_DROP'),
            PMA_hasNoRightsToDropDatabase(
                PMA_parseAndAnalyze('DROP TABLE tbl'),
                false,
                false
            )
        );

        $this->assertEquals(
            !defined('PMA_CHK_DROP'),
            PMA_hasNoRightsToDropDatabase(
                PMA_parseAndAnalyze('SELECT * from tbl'),
                false,
                false
            )
        );
    }

}