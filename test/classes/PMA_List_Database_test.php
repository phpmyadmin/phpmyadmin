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
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/List_Database.class.php';
require_once 'libraries/relation.lib.php';

class PMA_List_Database_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['cfg']['Server']['only_db'] = array('single\\_db');
        $this->object = $this->getMockForAbstractClass('PMA_List_Database');
    }

    /**
     * Call protected functions by making the visibitlity to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the protected method.
     */
    private function _callProtectedFunction($name, $params)
    {
        $class = new ReflectionClass('PMA_List_Database');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    public function testEmpty()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals('', $arr->getEmpty());
    }

    public function testSingle()
    {
        $arr = new PMA_List_Database;
        $this->assertEquals('single_db', $arr->getSingleItem());
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

    /**
     * Test for checkHideDatabase
     */
    public function testCheckHideDatabase()
    {
        $GLOBALS['cfg']['Server']['hide_db'] = 'single\\_db';
        $this->assertEquals(
            $this->_callProtectedFunction(
                'checkHideDatabase',
                array()
            ),
            ''
        );
    }

    /**
     * Test for getDefault
     */
    public function testGetDefault()
    {
        $GLOBALS['db'] = '';
        $this->assertEquals(
            $this->object->getDefault(),
            ''
        );

        $GLOBALS['db'] = 'mysql';
        $this->assertEquals(
            $this->object->getDefault(),
            'mysql'
        );
    }

    /**
     * Test for getGroupedDetails
     */
    public function testGetGroupedDetails()
    {
        $GLOBALS['cfg']['ShowTooltip'] = true;
        $GLOBALS['cfgRelation']['commwork'] = true;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['LeftFrameDBTree'] = true;
        $GLOBALS['cfg']['LeftFrameDBSeparator'] = array('|',',');

        $this->assertEquals(
            $this->object->getGroupedDetails(10, 100),
            array()
        );
    }

    /**
     * Test for getHtmlListGrouped
     */
    public function testGetHtmlListGrouped()
    {
        $GLOBALS['cfg']['ShowTooltip'] = true;
        $GLOBALS['cfgRelation']['commwork'] = true;
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['LeftFrameDBTree'] = true;
        $GLOBALS['cfg']['LeftFrameDBSeparator'] = array('|',',');

        $this->assertEquals(
            $this->object->getHtmlListGrouped(true,5,5),
            '<ul id="databaseList" lang="en" dir="ltr">
</ul>'
        );
    }
}
?>
