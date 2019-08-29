<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FormDisplay class in config folder
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Tests for PMA_FormDisplay class
 *
 * @package PhpMyAdmin-test
 */
class FormDisplayTest extends PmaTestCase
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
    protected function setUp(): void
    {
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['server'] = 0;
        $this->object = new FormDisplay(new ConfigFile());
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    protected function tearDown(): void
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
        $reflection = new ReflectionProperty(FormDisplay::class, '_jsLangStrings');
        $reflection->setAccessible(true);

        $this->assertCount(
            5,
            $reflection->getValue($this->object)
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
        $reflection = new ReflectionClass('PhpMyAdmin\Config\FormDisplay');

        $attrForms = $reflection->getProperty('_forms');
        $attrForms->setAccessible(true);

        $array = [
            "Servers" => [
                "1" => [
                    'test' => 1,
                    1 => ':group:end',
                ],
            ],
        ];

        $this->object->registerForm('pma_testform', $array, 2);
        $_forms = $attrForms->getValue($this->object);
        $this->assertInstanceOf(
            'PhpMyAdmin\Config\Form',
            $_forms['pma_testform']
        );

        $attrSystemPaths = $reflection->getProperty('_systemPaths');
        $attrSystemPaths->setAccessible(true);

        $this->assertEquals(
            [
                "Servers/2/test" => "Servers/1/test",
                "Servers/2/:group:end:0" => "Servers/1/:group:end:0",
            ],
            $attrSystemPaths->getValue($this->object)
        );

        $attrTranslatedPaths = $reflection->getProperty('_translatedPaths');
        $attrTranslatedPaths->setAccessible(true);

        $this->assertEquals(
            [
                "Servers/2/test" => "Servers-2-test",
                "Servers/2/:group:end:0" => "Servers-2-:group:end:0",
            ],
            $attrTranslatedPaths->getValue($this->object)
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

        $this->object = $this->getMockBuilder('PhpMyAdmin\Config\FormDisplay')
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $attrForms = new ReflectionProperty('PhpMyAdmin\Config\FormDisplay', '_forms');
        $attrForms->setAccessible(true);
        $attrForms->setValue($this->object, [1, 2, 3]);

        $this->object->expects($this->once())
            ->method('save')
            ->with([0, 1, 2], false)
            ->will($this->returnValue(true));

        $this->assertTrue(
            $this->object->process(false, false)
        );

        $attrForms->setValue($this->object, []);

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
        $reflection = new ReflectionClass('PhpMyAdmin\Config\FormDisplay');

        $attrIsValidated = $reflection->getProperty('_isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('_errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, []);

        $this->assertNull(
            $this->object->displayErrors()
        );

        $arr = [
            "Servers/1/test" => ['e1'],
            "foobar" => [
                'e2',
                'e3',
            ],
        ];

        $sysArr = [
            "Servers/1/test" => "Servers/1/test2",
        ];

        $attrSystemPaths = $reflection->getProperty('_systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $result = $this->object->displayErrors();

        $this->assertStringContainsString('<dt>Servers/1/test2</dt>', $result);
        $this->assertStringContainsString('<dd>e1</dd>', $result);
        $this->assertStringContainsString('<dt>Form_foobar</dt>', $result);
        $this->assertStringContainsString('<dd>e2</dd>', $result);
        $this->assertStringContainsString('<dd>e3</dd>', $result);
    }

    /**
     * Test for FormDisplay::fixErrors
     *
     * @return void
     */
    public function testFixErrors()
    {
        $reflection = new ReflectionClass('PhpMyAdmin\Config\FormDisplay');

        $attrIsValidated = $reflection->getProperty('_isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('_errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, []);

        $this->object->fixErrors();

        $arr = [
            "Servers/1/test" => ['e1'],
            "Servers/2/test" => [
                'e2',
                'e3',
            ],
            "Servers/3/test" => [],
        ];

        $sysArr = [
            "Servers/1/test" => "Servers/1/host",
        ];

        $attrSystemPaths = $reflection->getProperty('_systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $this->object->fixErrors();

        $this->assertEquals(
            [
                'Servers' => [
                    '1' => [
                        'test' => 'localhost',
                    ],
                ],
            ],
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
        $attrValidateSelect = new ReflectionMethod(
            'PhpMyAdmin\Config\FormDisplay',
            '_validateSelect'
        );
        $attrValidateSelect->setAccessible(true);

        $arr = ['foo' => 'var'];
        $value = 'foo';
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [
                    &$value,
                    $arr,
                ]
            )
        );

        $arr = ['' => 'foobar'];
        $value = null;
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [
                    &$value,
                    $arr,
                ]
            )
        );
        $this->assertEquals(
            "string",
            gettype($value)
        );

        $arr = [0 => 'foobar'];
        $value = 0;
        $this->assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [
                    &$value,
                    $arr,
                ]
            )
        );

        $arr = ['1' => 'foobar'];
        $value = 0;
        $this->assertFalse(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [
                    &$value,
                    $arr,
                ]
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
        $attrErrors = new ReflectionProperty('PhpMyAdmin\Config\FormDisplay', '_errors');
        $attrErrors->setAccessible(true);

        $this->assertFalse(
            $this->object->hasErrors()
        );

        $attrErrors->setValue(
            $this->object,
            [
                1,
                2,
            ]
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
        $method = new ReflectionMethod('PhpMyAdmin\Config\FormDisplay', '_getOptName');
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
        $method = new ReflectionMethod('PhpMyAdmin\Config\FormDisplay', '_loadUserprefsInfo');
        $method->setAccessible(true);

        $attrUserprefs = new ReflectionProperty(
            'PhpMyAdmin\Config\FormDisplay',
            '_userprefsDisallow'
        );

        $attrUserprefs->setAccessible(true);
        $method->invoke($this->object, null);
        $this->assertEquals(
            [],
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
        $method = new ReflectionMethod('PhpMyAdmin\Config\FormDisplay', '_setComments');
        $method->setAccessible(true);

        // recoding
        $opts = ['values' => []];
        $opts['values']['iconv'] = 'testIconv';
        $opts['values']['recode'] = 'testRecode';
        $opts['values']['mb'] = 'testMB';
        $opts['comment'] = null;
        $opts['comment_warning'] = null;

        $expect = $opts;

        $method->invokeArgs(
            $this->object,
            [
                'RecodingEngine',
                &$opts,
            ]
        );

        $expect['comment'] = '';
        if (! function_exists('iconv')) {
            $expect['values']['iconv'] .= " (unavailable)";
            $expect['comment'] = '"iconv" requires iconv extension';
        }
        if (! function_exists('recode_string')) {
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
            [
                'ZipDump',
                &$opts,
            ]
        );

        $comment = '';
        if (! function_exists("zip_open")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'zip_open.';
        }
        if (! function_exists("gzcompress")) {
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
            [
                'GZipDump',
                &$opts,
            ]
        );

        $comment = '';
        if (! function_exists("gzopen")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'gzopen.';
        }
        if (! function_exists("gzencode")) {
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
            [
                'BZipDump',
                &$opts,
            ]
        );

        $comment = '';
        if (! function_exists("bzopen")) {
            $comment = 'Compressed import will not work due to missing function ' .
                'bzopen.';
        }
        if (! function_exists("bzcompress")) {
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

        $GLOBALS['PMA_Config']->set('is_setup', false);

        $GLOBALS['cfg']['MaxDbList'] = 10;
        $GLOBALS['cfg']['MaxTableList'] = 10;
        $GLOBALS['cfg']['QueryHistoryMax'] = 10;

        $method->invokeArgs(
            $this->object,
            [
                'MaxDbList',
                &$opts,
            ]
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );

        $method->invokeArgs(
            $this->object,
            [
                'MaxTableList',
                &$opts,
            ]
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );

        $method->invokeArgs(
            $this->object,
            [
                'QueryHistoryMax',
                &$opts,
            ]
        );

        $this->assertEquals(
            "maximum 10",
            $opts['comment']
        );
    }
}
