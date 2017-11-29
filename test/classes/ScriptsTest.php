<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Script.php
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Scripts;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionClass;

/**
 * Tests for Script.php
 *
 * @package PhpMyAdmin-test
 */
class ScriptsTest extends PmaTestCase
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
        $this->object = new Scripts();
        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        }
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
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return the output from the private method.
     */
    private function _callPrivateFunction($name, $params)
    {
        $class = new ReflectionClass(Scripts::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _includeFile
     *
     * @return void
     *
     * @group medium
     */
    public function testIncludeFile()
    {
        $this->assertEquals(
            '<script data-cfasync="false" type="text/javascript" '
            . 'src="js/common.js?v=' . PMA_VERSION . '"></script>' . "\n",
            $this->_callPrivateFunction(
                '_includeFiles',
                array(
                    array(
                        array(
                            'has_onload' => false,
                            'filename' => 'common.js'
                        )
                    )
                )
            )
        );
    }

    /**
     * Test for getDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {

        $this->object->addFile('common.js');

        $this->assertRegExp(
            '@<script data-cfasync="false" type="text/javascript" '
            . 'src="js/common.js\?v=' . PMA_VERSION . '"></script>' . "\n"
            . '<script data-cfasync="false" type="text/'
            . 'javascript">// <!\\[CDATA\\[' . "\n"
            . 'AJAX.scriptHandler.add\\("common.js",1\\);' . "\n"
            . '\\$\\(function\\(\\) \\{AJAX.fireOnload\\("common.js"\\);\\}\\);'
            . "\n"
            . '// ]]></script>@',
            $this->object->getDisplay()
        );

    }

    /**
     * test for addCode
     *
     * @return void
     */
    public function testAddCode()
    {

        $this->object->addCode('alert(\'CodeAdded\');');

        $this->assertEquals(
            '<script data-cfasync="false" type="text/javascript">// <![CDATA[
alert(\'CodeAdded\');
AJAX.scriptHandler;
$(function() {});
// ]]></script>',
            $this->object->getDisplay()
        );
    }

     /**
     * test for getFiles
     *
     * @return void
     */
    public function testGetFiles()
    {
        // codemirror's onload event is blacklisted
        $this->object->addFile('vendor/codemirror/lib/codemirror.js');

        $this->object->addFile('common.js');
        $this->assertEquals(
            array(
                array('name' => 'vendor/codemirror/lib/codemirror.js', 'fire' => 0),
                array('name' => 'common.js', 'fire' => 1)
            ),
            $this->object->getFiles()
        );
    }

    /**
     * test for addFile
     *
     * @return void
     */
    public function testAddFile()
    {
        // Assert empty _files property of
        // Scripts
        $this->assertAttributeEquals(
            array(),
            '_files',
            $this->object
        );

        // Add one script file
        $file = 'common.js';
        $hash = 'd7716810d825f4b55d18727c3ccb24e6';
        $_files = array(
            $hash => array(
                'has_onload' => 1,
                'filename' => 'common.js',
                'params' => array(),
            )
        );
        $this->object->addFile($file);
        $this->assertAttributeEquals(
            $_files,
            '_files',
            $this->object
        );

    }

    /**
     * test for addFiles
     *
     * @return void
     */
    public function testAddFiles()
    {
        $filenames = array(
            'common.js',
            'sql.js',
            'common.js',
        );
        $_files = array(
            'd7716810d825f4b55d18727c3ccb24e6' => array(
                'has_onload' => 1,
                'filename' => 'common.js',
                'params' => array(),
            ),
            '347a57484fcd6ea6d8a125e6e1d31f78' => array(
                'has_onload' => 1,
                'filename' => 'sql.js',
                'params' => array(),
            ),
        );
        $this->object->addFiles($filenames);
        $this->assertAttributeEquals(
            $_files,
            '_files',
            $this->object
        );
    }
}
