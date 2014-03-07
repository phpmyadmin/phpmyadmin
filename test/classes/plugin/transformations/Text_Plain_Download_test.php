<?php
/**
 * Tests for Application_Octetstream_Download class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Application_Octetstream_Download.class.php';

/**
 * Tests for Application_Octetstream_Download class
 *
 * @package PhpMyAdmin-test
 */
class Application_Octetstream_Download_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new Application_Octetstream_Download(new PluginManager());
        global $row, $fields_meta;
        $fields_meta = array();
        $row = array("pma"=>"aaa", "pca"=>"bbb");
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
        $info = 'Displays a link to download the binary data of the column. You can'
            . ' use the first option to specify the filename, or use the second'
            . ' option as the name of a column which contains the filename. If'
            . ' you use the second option, you need to set the first option to'
            . ' the empty string.';
        $this->assertEquals(
            $info,
            Application_Octetstream_Download::getInfo()
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
            "Download",
            Application_Octetstream_Download::getName()
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
            "Application",
            Application_Octetstream_Download::getMIMEType()
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
            "OctetStream",
            Application_Octetstream_Download::getMIMESubtype()
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
        $options = array("filename", 'wrapper_link'=>'PMA_wrapper_link');
        $result = '<a href="transformation_wrapper.phpPMA_wrapper_link'
        . '&amp;ct=application/octet-stream&amp;cn=filename" '
        . 'title="filename">filename</a>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );

        //using default filename: binary_file.dat
        $options = array("", 'cloumn', 'wrapper_link'=>'PMA_wrapper_link');
        $result = '<a href="transformation_wrapper.phpPMA_wrapper_link&amp;'
            . 'ct=application/octet-stream&amp;cn=binary_file.dat" '
            . 'title="binary_file.dat">binary_file.dat</a>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($buffer, $options)
        );
    }
}
