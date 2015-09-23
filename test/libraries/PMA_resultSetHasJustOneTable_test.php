<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_resultSetHasJustOneTable method
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sql.lib.php';

/**
 * Tests for PMA_resultSetHasJustOneTable method
 *
 * @package PhpMyAdmin-test
 */
class PMA_ResultSetHasJustOneTableTest extends PHPUnit_Framework_TestCase
{

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
        $this->assertFalse(PMA_resultSetHasJustOneTable($fields_meta));

        // should not matter on where the odd column occurs
        $fields_meta = array($col2, $col3, $col1);
        $this->assertFalse(PMA_resultSetHasJustOneTable($fields_meta));

        $fields_meta = array($col3, $col1, $col2);
        $this->assertFalse(PMA_resultSetHasJustOneTable($fields_meta));
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

        $this->assertTrue(PMA_resultSetHasJustOneTable($fields_meta));
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
        $this->assertTrue(PMA_resultSetHasJustOneTable($fields_meta));

        // should not matter on where the function column occurs
        $fields_meta = array($col2, $col3, $col1);
        $this->assertTrue(PMA_resultSetHasJustOneTable($fields_meta));

        $fields_meta = array($col3, $col1, $col2);
        $this->assertTrue(PMA_resultSetHasJustOneTable($fields_meta));
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

        $this->assertFalse(PMA_resultSetHasJustOneTable($fields_meta));
    }
}
