<?php
/**
 * Tests for Text_Plain_External class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_External.class.php';

/**
 * Tests for Text_Plain_External class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_External_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Text_Plain_External(new PluginManager());
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
        $info
            = 'LINUX ONLY: Launches an external application and feeds it the column'
            . ' data via standard input. Returns the standard output of the'
            . ' application. The default is Tidy, to pretty-print HTML code.'
            . ' For security reasons, you have to manually edit the file'
            . ' libraries/plugins/transformations/Text_Plain_External'
            . '.class.php and list the tools you want to make available.'
            . ' The first option is then the number of the program you want to'
            . ' use and the second option is the parameters for the program.'
            . ' The third option, if set to 1, will convert the output using'
            . ' htmlspecialchars() (Default 1). The fourth option, if set to 1,'
            . ' will prevent wrapping and ensure that the output appears all on'
            . ' one line (Default 1).';
        $this->assertEquals(
            $info,
            Text_Plain_External::getInfo()
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
            "External",
            Text_Plain_External::getName()
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
            Text_Plain_External::getMIMEType()
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
            Text_Plain_External::getMIMESubtype()
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
        $buffer = "PMA_BUFFER";
        $options = array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q");
        $this->assertEquals(
            "PMA_BUFFER",
            $this->object->applyTransformation($buffer, $options)
        );
    }

    /**
     * Test for applyTransformationNoWrap
     *
     * @return void
     *
     * @group medium
     */
    public function testApplyTransformationNoWrap()
    {
        $options = array("/dev/null -i -wrap -q", "/dev/null -i -wrap -q");
        $this->assertEquals(
            true,
            $this->object->applyTransformationNoWrap($options)
        );
        $options = array(
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q", 1
        );
        $this->assertEquals(
            true,
            $this->object->applyTransformationNoWrap($options)
        );
        $options = array(
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q", "1"
        );
        $this->assertEquals(
            true,
            $this->object->applyTransformationNoWrap($options)
        );
        $options = array(
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q",
            "/dev/null -i -wrap -q",
            2
        );
        $this->assertEquals(
            false,
            $this->object->applyTransformationNoWrap($options)
        );
    }
}
