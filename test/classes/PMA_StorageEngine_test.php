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
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';

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
    public function testGetStorageEngines()
    {

        $this->assertEquals(
            array(
                'dummy' => array(
                    'Engine' => 'dummy',
                    'Support' => 'YES',
                    'Comment' => 'dummy comment',
                ),
                'dummy2' => array(
                    'Engine' => 'dummy2',
                    'Support' => 'NO',
                    'Comment' => 'dummy2 comment',
                ),
            ),
            $this->object->getStorageEngines()
        );
    }

    /**
     * Test for getHtmlSelect
     *
     * @group medium
     */
    public function testGetHtmlSelect()
    {

        $this->assertEquals(
            '<select name="engine">
    <option value="dummy" title="dummy comment">
        dummy
    </option>
</select>
',
            $this->object->getHtmlSelect()
        );
    }

    /**
     * Test for getEngine
     */
    public function testGetEngine()
    {

        $this->assertInstanceOf(
            'PMA_StorageEngine',
            $this->object->getEngine('dummy')
        );
    }

    /**
     * Test for isValid
     */
    public function testIsValid()
    {

        $this->assertTrue(
            $this->object->isValid('PBMS')
        );
        $this->assertTrue(
            $this->object->isValid('dummy')
        );
        $this->assertTrue(
            $this->object->isValid('dummy2')
        );
        $this->assertFalse(
            $this->object->isValid('invalid')
        );
    }

    /**
     * Test for getPage
     */
    public function testGetPage()
    {

        $this->assertFalse(
            $this->object->getPage(1)
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages()
    {

        $this->assertEquals(
            array(),
            $this->object->getInfoPages()
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern()
    {

        $this->assertFalse(
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage()
    {

        $this->assertEquals(
            'dummy-storage-engine',
            $this->object->getMysqlHelpPage()
        );
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables()
    {

        $this->assertEquals(
            array(),
            $this->object->getVariables()
        );
    }

    /**
     * Test for getSupportInformationMessage
     */
    public function testGetSupportInformationMessage()
    {
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 1;
        $this->assertEquals(
            'dummy has been disabled for this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 2;
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 3;
        $this->assertEquals(
            'dummy is the default storage engine on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );
    }

    /**
     * Test for getComment
     */
    public function testGetComment()
    {

        $this->assertEquals(
            'dummy comment',
            $this->object->getComment()
        );
    }

    /**
     * Test for getTitle
     */
    public function testGetTitle()
    {

        $this->assertEquals(
            'dummy',
            $this->object->getTitle()
        );
    }

    /**
     * Test for engine_init
     */
    public function testEngine_init()
    {

        $this->assertNull(
            $this->object->engine_init()
        );
    }

    /**
     * Test for resolveTypeSize
     */
    public function testResolveTypeSize()
    {

        $this->assertEquals(
            array(
                0 => 12,
                1 => 'B'
            ),
            $this->object->resolveTypeSize(12)
        );
    }
}
