<?php
/**
 * Tests for Text_Plain_Sql class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/Util.class.php';
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Sql.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/sqlparser.lib.php';

/**
 * Tests for Text_Plain_Sql class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Sql_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_Sql(new PluginManager());
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
     * Test for getInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetInfo()
    {
        $info = 'Formats text as SQL query with syntax highlighting.';
        $this->assertEquals(
            $info,
            Text_Plain_Sql::getInfo()
        );

    }

    /**
     * Test for getName
     *
     * @return void
     *
     * @group medium
     */
    public function testGetName()
    {
        $this->assertEquals(
            "SQL",
            Text_Plain_Sql::getName()
        );
    }

    /**
     * Test for getMIMEType
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMEType()
    {
        $this->assertEquals(
            "Text",
            Text_Plain_Sql::getMIMEType()
        );
    }

    /**
     * Test for getMIMESubtype
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMESubtype()
    {
        $this->assertEquals(
            "Plain",
            Text_Plain_Sql::getMIMESubtype()
        );
    }

    /**
     * Test for applyTransformation
     *
     * @return void
     *
     * @group medium
     */
    public function testApplyTransformation()
    {
        $buffer = "select *";
        $options = array("option1", "option2");
        $result = '<code class="sql"><pre>' . "\n"
            . 'select *' . "\n"
            . '</pre></code>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
