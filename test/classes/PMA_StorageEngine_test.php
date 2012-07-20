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
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/CommonFunctions.class.php';

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
                    'dummy' => 'table1',
                    'engine' => 'table`2'
                );
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
                'engine' => 'table`2'
            )
        );
    }

    /**
     * Test for getHtmlSelect
     *
     * @group medium
     */
    public function testGetHtmlSelect(){

        $this->assertEquals(
            $this->object->getHtmlSelect(),
            '<select name="engine">
    <option value="dummy" title="t">
        t
    </option>
    <option value="engine" title="t">
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

    /**
     * Test for getPage
     */
    public function testGetPage(){

        $this->assertFalse(
            $this->object->getPage(1)
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(){

        $this->assertEquals(
            $this->object->getInfoPages(),
            array()
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(){

        $this->assertFalse(
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(){

        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'dummy-storage-engine'
        );
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables(){

        $this->assertEquals(
            $this->object->getVariables(),
            array()
        );
    }

    /**
     * Test for getSupportInformationMessage
     */
    public function testGetSupportInformationMessage(){
        $this->assertEquals(
            $this->object->getSupportInformationMessage(),
            'This MySQL server does not support the t storage engine.'
        );

        $this->object->support = 1;
        $this->assertEquals(
            $this->object->getSupportInformationMessage(),
            't has been disabled for this MySQL server.'
        );

        $this->object->support = 2;
        $this->assertEquals(
            $this->object->getSupportInformationMessage(),
            't is available on this MySQL server.'
        );

        $this->object->support = 3;
        $this->assertEquals(
            $this->object->getSupportInformationMessage(),
            't is the default storage engine on this MySQL server.'
        );
    }

    /**
     * Test for getComment
     */
    public function testGetComment(){

        $this->assertEquals(
            $this->object->getComment(),
            't'
        );
    }

    /**
     * Test for getTitle
     */
    public function testGetTitle(){

        $this->assertEquals(
            $this->object->getTitle(),
            't'
        );
    }

    /**
     * Test for engine_init
     */
    public function testEngine_init(){

        $this->assertNull(
            $this->object->engine_init()
        );
    }

    /**
     * Test for resolveTypeSize
     */
    public function testResolveTypeSize(){

        $this->assertEquals(
            $this->object->resolveTypeSize(12),
            array(
                0 => 12,
                1 => 'B'
            )
        );
    }
}
