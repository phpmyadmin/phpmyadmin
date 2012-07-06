<?php
/**
 * Tests for StorageEngine.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/dbi/mysql.dbi.lib.php';

class PMA_StorageEngine_test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        if (! defined('PMA_DRIZZLE')) {
            define('PMA_DRIZZLE', 1);
        }
        if (! function_exists('PMA_DBI_fetch_result')) {
            function PMA_DBI_fetch_result($query)
            {
                return array(
                    'dummy' =>'table1',
                    'table`2');
            }
        }
        $this->object = $this->getMockForAbstractClass('PMA_StorageEngine', array('dummy'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for getStorageEngines
     */
    public function testGetStorageEngines(){

        $this->assertEquals(
            $this->object->getStorageEngines(),
            array(
                'dummy' => 'table1',
                0 => 'table`2'
            )
        );
    }

    /**
     * Test for getHtmlSelect
     */
    public function testGetHtmlSelect(){

        $this->assertEquals(
            $this->object->getHtmlSelect(),
            '<select name="engine">
    <option value="dummy" title="t">
        t
    </option>
    <option value="0" title="t">
        t
    </option>
</select>
'
        );
    }

    /**
     * Test for getEngine
     */
    public function testGetEngine(){

        $this->assertTrue(
            $this->object->getEngine('dummy') instanceof PMA_StorageEngine
        );
    }

    /**
     * Test for isValid
     */
    public function testIsValid(){

        $this->assertTrue(
            $this->object->isValid('PBMS')
        );
        $this->assertTrue(
            $this->object->isValid('dummy')
        );
    }

}
