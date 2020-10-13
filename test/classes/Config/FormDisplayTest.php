<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use function function_exists;
use function gettype;

class FormDisplayTest extends AbstractTestCase
{
    /** @var FormDisplay */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        parent::loadDefaultConfig();
        parent::setGlobalConfig();
        $GLOBALS['server'] = 0;
        $this->object = new FormDisplay(new ConfigFile());
        Form::resetGroupCounter();
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for FormDisplay::__constructor
     *
     * @group medium
     */
    public function testFormDisplayContructor(): void
    {
        $reflection = new ReflectionProperty(FormDisplay::class, 'jsLangStrings');
        $reflection->setAccessible(true);

        $this->assertCount(
            5,
            $reflection->getValue($this->object)
        );
    }

    /**
     * Test for FormDisplay::registerForm
     *
     * @group medium
     */
    public function testRegisterForm(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrForms = $reflection->getProperty('forms');
        $attrForms->setAccessible(true);

        $array = [
            'Servers' => [
                '1' => [
                    'test' => 1,
                    1 => ':group:end',
                ],
            ],
        ];

        $this->object->registerForm('pma_testform', $array, 2);
        $_forms = $attrForms->getValue($this->object);
        $this->assertInstanceOf(
            Form::class,
            $_forms['pma_testform']
        );

        $attrSystemPaths = $reflection->getProperty('systemPaths');
        $attrSystemPaths->setAccessible(true);

        $this->assertEquals(
            [
                'Servers/2/test' => 'Servers/1/test',
                'Servers/2/:group:end:0' => 'Servers/1/:group:end:0',
            ],
            $attrSystemPaths->getValue($this->object)
        );

        $attrTranslatedPaths = $reflection->getProperty('translatedPaths');
        $attrTranslatedPaths->setAccessible(true);

        $this->assertEquals(
            [
                'Servers/2/test' => 'Servers-2-test',
                'Servers/2/:group:end:0' => 'Servers-2-:group:end:0',
            ],
            $attrTranslatedPaths->getValue($this->object)
        );
    }

    /**
     * Test for FormDisplay::process
     *
     * @group medium
     */
    public function testProcess(): void
    {
        $this->assertFalse(
            $this->object->process(true, true)
        );

        $this->object = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->setMethods(['save'])
            ->getMock();

        $attrForms = new ReflectionProperty(FormDisplay::class, 'forms');
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
     */
    public function testDisplayErrors(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrIsValidated = $reflection->getProperty('isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, []);

        $result = $this->object->displayErrors();

        $this->assertNull($result);

        $arr = [
            'Servers/1/test' => ['e1'],
            'foobar' => [
                'e2',
                'e3',
            ],
        ];

        $sysArr = ['Servers/1/test' => 'Servers/1/test2'];

        $attrSystemPaths = $reflection->getProperty('systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $result = $this->object->displayErrors();

        $this->assertIsString($result);
        $this->assertStringContainsString('<dt>Servers/1/test2</dt>', $result);
        $this->assertStringContainsString('<dd>e1</dd>', $result);
        $this->assertStringContainsString('<dt>Form_foobar</dt>', $result);
        $this->assertStringContainsString('<dd>e2</dd>', $result);
        $this->assertStringContainsString('<dd>e3</dd>', $result);
    }

    /**
     * Test for FormDisplay::fixErrors
     */
    public function testFixErrors(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrIsValidated = $reflection->getProperty('isValidated');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('errors');
        $attrIsValidated->setAccessible(true);
        $attrIsValidated->setValue($this->object, []);

        $this->object->fixErrors();

        $arr = [
            'Servers/1/test' => ['e1'],
            'Servers/2/test' => [
                'e2',
                'e3',
            ],
            'Servers/3/test' => [],
        ];

        $sysArr = ['Servers/1/test' => 'Servers/1/host'];

        $attrSystemPaths = $reflection->getProperty('systemPaths');
        $attrSystemPaths->setAccessible(true);
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $this->object->fixErrors();

        $this->assertEquals(
            [
                'Servers' => [
                    '1' => ['test' => 'localhost'],
                ],
            ],
            $_SESSION['ConfigFile0']
        );
    }

    /**
     * Test for FormDisplay::validateSelect
     */
    public function testValidateSelect(): void
    {
        $attrValidateSelect = new ReflectionMethod(
            FormDisplay::class,
            'validateSelect'
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
            'string',
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
     */
    public function testHasErrors(): void
    {
        $attrErrors = new ReflectionProperty(FormDisplay::class, 'errors');
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
     */
    public function testGetDocLink(): void
    {
        $this->assertEquals(
            './url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F' .
            'config.html%23cfg_Servers_3_test_2_',
            $this->object->getDocLink('Servers/3/test/2/')
        );

        $this->assertEquals(
            '',
            $this->object->getDocLink('Import')
        );

        $this->assertEquals(
            '',
            $this->object->getDocLink('Export')
        );
    }

    /**
     * Test for FormDisplay::getOptName
     */
    public function testGetOptName(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'getOptName');
        $method->setAccessible(true);

        $this->assertEquals(
            'Servers_',
            $method->invoke($this->object, 'Servers/1/')
        );

        $this->assertEquals(
            'Servers_23_',
            $method->invoke($this->object, 'Servers/1/23/')
        );
    }

    /**
     * Test for FormDisplay::loadUserprefsInfo
     */
    public function testLoadUserprefsInfo(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'loadUserprefsInfo');
        $method->setAccessible(true);

        $attrUserprefs = new ReflectionProperty(
            FormDisplay::class,
            'userprefsDisallow'
        );

        $attrUserprefs->setAccessible(true);
        $method->invoke($this->object, null);
        $this->assertEquals(
            [],
            $attrUserprefs->getValue($this->object)
        );
    }

    /**
     * Test for FormDisplay::setComments
     */
    public function testSetComments(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'setComments');
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
            $expect['values']['iconv'] .= ' (unavailable)';
            $expect['comment'] = '"iconv" requires iconv extension';
        }
        if (! function_exists('recode_string')) {
            $expect['values']['recode'] .= ' (unavailable)';
            $expect['comment'] .= ($expect['comment'] ? ', ' : '') .
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
        if (! function_exists('zip_open')) {
            $comment = 'Compressed import will not work due to missing function ' .
                'zip_open.';
        }
        if (! function_exists('gzcompress')) {
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
        if (! function_exists('gzopen')) {
            $comment = 'Compressed import will not work due to missing function ' .
                'gzopen.';
        }
        if (! function_exists('gzencode')) {
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
        if (! function_exists('bzopen')) {
            $comment = 'Compressed import will not work due to missing function ' .
                'bzopen.';
        }
        if (! function_exists('bzcompress')) {
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
            'maximum 10',
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
            'maximum 10',
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
            'maximum 10',
            $opts['comment']
        );
    }
}
