<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_List_Database class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
require_once 'libraries/List_Database.class.php';

class PMA_List_Database_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['cfg']['Server']['only_db'] = array('single\\_db');
    }

    public function testEmpty()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals('', $arr->getEmpty());
    }

    public function testSingle()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals(true, $arr->getSingleItem());
    }

    public function testExists()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals(true, $arr->exists('single_db'));
    }

    public function testLimitedItems()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals(array('single_db'), $arr->getLimitedItems(0, 1));
    }

    public function testLimitedItems_empty()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals(array(), $arr->getLimitedItems(1, 1));
    }

    public function testHtmlOptions()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals('<option value="single_db">single_db</option>' . "\n", $arr->getHtmlOptions());
    }
}
?>
