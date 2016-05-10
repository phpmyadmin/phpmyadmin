<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\Config;
use PMA\libraries\config\ConfigFile;
use PMA\libraries\config\FormDisplay;
use PMA\libraries\Theme;

require_once 'test/PMATestCase.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/user_preferences.lib.php';

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class FormDisplayTest extends PMATestCase
{
    /**
     * @var FormDisplay
     */
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['server'] = 0;
        $this->object = new FormDisplay(new ConfigFile());
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for FormDisplay::__constructor
     *
     * @return void
     * @group medium
     */
    public function testFormDisplayContructor()
    {
        $this->assertCount(
            5,
            $this->readAttribute($this->object, '_jsLangStrings')
        );
    }

    /**
     * Test for FormDisplay::registerForm
     *
     * @return void
     * @group medium
     */
    public function testRegisterForm()
    {
        $reflection = new \ReflectionClass('PMA\libraries\config\FormDisplay');

        $attrForms = $reflection->getProperty('_forms');
        $attrForms->setAccessible(true);

        $array = array(
            "Servers" => array(
                "1" => array(
                    'test' => 1,
                    1 => ':group:end'
                )
            )
        );

        $this->object->registerForm('pma_testform', $array, 2);
        $_forms = $attrForms->getValue($this->object);
        $this->assertInstanceOf(
            'PMA\libraries\config\Form',
            $_forms['pma_testform']
        );

        $this->assertEquals(
            array(
                "Servers/2/test" => "Servers/1/test",
                "Servers/2/:group:end:0" => "Servers/1/:group:end:0"
            ),
            $this->readAttribute($this->object, '_systemPaths')
        );

        $this->assertEquals(
            array(
                "Servers/2/test" => "Servers-2-test",
                "Servers/2/:group:end:0" => "Servers-2-:group:end:0"
            ),
            $this->readAttribute($this->object, '_translatedPaths')
        );
    }

    /**
     * Test for FormDisplay::process
     *
     * @return void
     * @group medium
     */
    public function testProcess()
    {
        $this->assertFalse(
            $this->object->process(true, true)
        );

        $this->object = $this->getMockBuilder('PMA\libraries\config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(array('save'))
            ->getMock();

        $attrForms = new \ReflectionProperty('PMA\libraries\config\FormDisplay', '_forms');
        $attrForms->setAccessible(true);
        $attrForms->setValue($this->object, array(1, 2, 3));

        $this->object->expects($this->once())
            ->method('save')
            ->with(array(0, 1, 2), false)
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->object->process(false, false)
        );

        $attrForms->setValue($this->object, array());

        $this->assertFalse(
            $this->object->process(false, false)
        );
    }

    /**
     * Test for FormDisplay::displayErrors
     *
     * @return void
     */
    public function testDisplayErrors()
    {
        $reflection = new \ReflectionClass('PMA\libraries\config\FormDisplay');

        $attrIsValidated = $reflection->getProperty('_isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('_errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, array());

        $this->assertNull(
            $this->object->displayErrors()
        );

        $arr = array(
            "Servers/1/test" => array('e1'),
            "foobar" => array('e2', 'e3')
        );

        $sysArr = array(
            "Servers/1/test" => "Servers/1/test2"
        );

        $attrSystemPaths = $reflection->getProperty('_systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $GLOBALS['strConfigForm_foobar'] = 'foobar123';

        $result = $this->object->displayErrors();

        $this->assertEquals(
            '<dl><dt>Servers_test2_name</dt>' .
            '<dd>e1</dd></dl><dl><dt>foobar123</dt><dd>' .
            'e2</dd><dd>e3</dd></dl>',
            $result
        );
    }

    /**
     * Test for FormDisplay::fixErrors
     *
     * @return void
     */
    public function testFixErrors()
    {
        $reflection = new \ReflectionClass('PMA\libraries\config\FormDisplay');

        $attrIsValidated = $reflection->getProperty('_isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('_errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, array());

        $this->assertNull(
            $this->object->fixErrors()
        );

        $arr = array(
            "Servers/1/test" => array('e1'),
            "Servers/2/test" => array('e2', 'e3'),
            "Servers/3/test" => array()
        );

        $sysArr = array(
            "Servers/1/test" => "Servers/1/connect_type"
        );

        $attrSystemPaths = $reflection->getProperty('_systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $this->object->fixErrors();

        $this->assertEquals(
            array(
                'Servers' => array(
                    '1' => array(
                        'test' => 'tcp'
                    )
                )
            ),
            $_SESSION['ConfigFile0']
        );
    }

    /**
     * Test for FormDisplay::_validateSelect
     *
     * @return void
     */
    public function testValidateSelect()
    {
        $attrValidateSelect = new \ReflectionMethod(
            'PMA\libraries\config\FormDisplay',
            '_validateSelect'
        );
        $attrValidateSelect->setAccessible(true);

        $arr = array('foo' => 'var');
        $value = 'foo';
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                array(&$value, $arr)
            )
        );

        $arr = array('' => 'foobar');
        $value = null;
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                array(&$value, $arr)
            )
        );
        $this->assertEquals(
            "string",
            gettype($value)
        );

        $arr = array(0 => 'foobar');
        $value = 0;
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                array(&$value, $arr)
            )
        );

        $arr = array('1' => 'foobar');
        $value = 0;
        $this->assertFalse(
            $attrValidateSelect->invokeArgs(
                $this->object,
                array(&$value, $arr)
            )
        );
    }

    /**
     * Test for FormDisplay::hasErrors
     *
     * @return void
     */
    public function testHasErrors()
    {
        $attrErrors = new \ReflectionProperty('PMA\libraries\config\FormDisplay', '_errors');
        $attrErrors->setAccessible(true);

        $this->assertFalse(
            $this->object->hasErrors()
        );

        $attrErrors->setValue(
            $this->object,
            array(1, 2)
        );

        $this->assertTrue(
            $this->object->hasErrors()
        );
    }

    /**
     * Test for FormDisplay::getDocLink
     *
     * @return void
     */
    public function testGetDocLink()
    {
        $this->assertEquals(
            "./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F" .
            "config.html%23cfg_Servers_3_test_2_",
            $this->object->getDocLink("Servers/3/test/2/")
        );

        $this->assertEquals(
            '',
            $this->object->getDocLink("Import")
        );

        $this->assertEquals(
            '',
            $this->object->getDocLink("Export")
        );
    }

    /**
     * Test for FormDisplay::_getOptName
     *
     * @return void
     */
    public function testGetOptName()
    {
        $method = new \ReflectionMethod('PMA\libraries\config\FormDisplay', '_getOptName');
        $method->setAccessible(true);

        $this->assertEquals(
            "Servers_",
            $method->invoke($this->object, "Servers/1/")
        );

        $this->assertEquals(
            "Servers_23_",
            $method->invoke($this->object, "Servers/1/23/")
        );
    }

    /**
     * Test for FormDisplay::_loadUserprefsInfo
     *
     * @return void
     */
    public function testLoadUserprefsInfo()
    {
        $method = new \ReflectionMethod('PMA\libraries\config\FormDisplay', '_loadUserprefsInfo');
        $method->setAccessible(true);

        $attrUserprefs = new \ReflectionProperty(
            'PMA\libraries\config\FormDisplay',
            '_userprefsDisallow'
        );

        $attrUserprefs->setAccessible(true);
        $method->invoke($this->object, null);
        $this->assertEquals(
            array(),
            $attrUserprefs->getValue($this->object)
        );
    }

    /**
     * Test for FormDisplay::_setComments
     *
     * @return void
     */
    public function testSetComments()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped('Cannot redefine constant');
        }

        $method = new \ReflectionMethod('PMA\libraries\config\FormDisplay', '_setComments');
        $method->setAccessible(true);

        // recoding
        $opts = array('values' => array());
        $opts['values']['iconv'] = 'testIconv';
        $opts['values']['recode'] = 'testRecode';

        $expect = $opts;

        $method->invokeArgs(
            $this->object,
            array('RecodingEngine', &$opts)
        );

        $expect['comment'] = '';
        if (!function_exists('iconv')) {
            $expect['values']['iconv'] .= " (unavailable)";
            $expect['comment'] = '"iconv" requires iconv extension';
        }
        if (!function_exists('recode_string')) {
            $expect['values']['recode'] .= " (unavailable)";
            $expect['comment'] .= ($expect['comment'] ? ", " : '') .
                '"recode" requires recode extension';
        }
        $expect['comment_warning'] = 1;

        $this->assertEquals(
            $expect,
            $opts
        );

        // ZipDump, GZipDump, BZipDump
        $method->invokeArgs(
            $this->object,
            array('ZipDump', &$opts)
        );

        $comment = '';
        if (!function_exists("zip_open")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'zip_open.';
        }
        if (!function_exists("gzcompress")) {
            $comment .= ($comment ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function gzcompress.';
        }

        $this->assertEquals(
            $comment,
            $opts['comment']
        );

        $this->assertTrue(
            $opts['comment_warning']
        );

        $method->invokeArgs(
            $this->object,
            array('GZipDump', &$opts)
        );

        $comment = '';
        if (!function_exists("gzopen")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'gzopen.';
        }
        if (!function_exists("gzencode")) {
            $comment .= ($comment ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function gzencode.';
        }

        $this->assertEquals(
            $comment,
            $opts['comment']
        );

        $this->assertTrue(
            $opts['comment_warning']
        );

        $method->invokeArgs(
            $this->object,
            array('BZipDump', &$opts)
        );

        $comment = '';
        if (!function_exists("bzopen")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'bzopen.';
        }
        if (!function_exists("bzcompress")) {
            $comment .= ($comment ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function bzcompress.';
        }

        $this->assertEquals(
            $comment,
            $opts['comment']
        );

        $this->assertTrue(
            $opts['comment_warning']
        );

        if (defined('PMA_SETUP')) {
            runkit_constant_remove('PMA_SETUP');
        }

        $GLOBALS['cfg']['MaxDbList'] = 10;
        $GLOBALS['cfg']['MaxTableList'] = 10;
        $GLOBALS['cfg']['QueryHistoryMax'] = 10;

        $method->invokeArgs(
            $this->object,
            array('MaxDbList', &$opts)
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );

        $method->invokeArgs(
            $this->object,
            array('MaxTableList', &$opts)
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );

        $method->invokeArgs(
            $this->object,
            array('QueryHistoryMax', &$opts)
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );

    }


}
