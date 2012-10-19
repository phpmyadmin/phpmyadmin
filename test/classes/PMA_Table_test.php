<?php
/**
 * Tests for Table.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Table.class.php';

/**
 * Tests behaviour of PMA_Table class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Table_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test object creating
     *
     * @return void
     */
    public function testCreate()
    {
        $table = new PMA_Table('pma_test', 'table1');
        $this->assertInstanceOf('PMA_Table', $table);
    }

}

