<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Current;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function function_exists;

#[CoversClass(FormDisplay::class)]
#[Medium]
class FormDisplayTest extends AbstractTestCase
{
    protected FormDisplay $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Current::$server = 2;
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
     * Test for FormDisplay::registerForm
     */
    public function testRegisterForm(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrForms = $reflection->getProperty('forms');

        $array = ['Servers' => ['1' => ['test' => 1, 1 => ':group:end']]];

        $this->object->registerForm('pma_testform', $array, 2);
        $forms = $attrForms->getValue($this->object);
        self::assertInstanceOf(Form::class, $forms['pma_testform']);

        $attrSystemPaths = $reflection->getProperty('systemPaths');

        self::assertSame(
            ['Servers/2/test' => 'Servers/1/test', 'Servers/2/:group:end:0' => 'Servers/1/:group:end:0'],
            $attrSystemPaths->getValue($this->object),
        );

        $attrTranslatedPaths = $reflection->getProperty('translatedPaths');

        self::assertSame(
            ['Servers/2/test' => 'Servers-2-test', 'Servers/2/:group:end:0' => 'Servers-2-:group:end:0'],
            $attrTranslatedPaths->getValue($this->object),
        );
    }

    /**
     * Test for FormDisplay::process
     */
    public function testProcess(): void
    {
        self::assertFalse(
            $this->object->process(true, true),
        );

        $this->object = $this->getMockBuilder(FormDisplay::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();

        $attrForms = new ReflectionProperty(FormDisplay::class, 'forms');
        $attrForms->setValue($this->object, [1, 2, 3]);

        $this->object->expects(self::once())
            ->method('save')
            ->with([0, 1, 2], false)
            ->willReturn(true);

        self::assertTrue(
            $this->object->process(false, false),
        );

        $attrForms->setValue($this->object, []);

        self::assertFalse(
            $this->object->process(false, false),
        );
    }

    /**
     * Test for FormDisplay::displayErrors
     */
    public function testDisplayErrors(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrIsValidated = $reflection->getProperty('isValidated');
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('errors');
        $attrIsValidated->setValue($this->object, []);

        $result = $this->object->displayErrors();

        self::assertSame($result, '');

        $arr = ['Servers/1/test' => ['e1'], 'foobar' => ['e2', 'e3']];

        $sysArr = ['Servers/1/test' => 'Servers/1/test2'];

        $attrSystemPaths = $reflection->getProperty('systemPaths');
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $result = $this->object->displayErrors();

        self::assertStringContainsString('<dt>Servers/1/test2</dt>', $result);
        self::assertStringContainsString('<dd>e1</dd>', $result);
        self::assertStringContainsString('<dt>Form_foobar</dt>', $result);
        self::assertStringContainsString('<dd>e2</dd>', $result);
        self::assertStringContainsString('<dd>e3</dd>', $result);
    }

    /**
     * Test for FormDisplay::fixErrors
     */
    public function testFixErrors(): void
    {
        $reflection = new ReflectionClass(FormDisplay::class);

        $attrIsValidated = $reflection->getProperty('isValidated');
        $attrIsValidated->setValue($this->object, true);

        $attrIsValidated = $reflection->getProperty('errors');
        $attrIsValidated->setValue($this->object, []);

        $this->object->fixErrors();

        $arr = ['Servers/1/test' => ['e1'], 'Servers/2/test' => ['e2', 'e3'], 'Servers/3/test' => []];

        $sysArr = ['Servers/1/test' => 'Servers/1/host'];

        $attrSystemPaths = $reflection->getProperty('systemPaths');
        $attrSystemPaths->setValue($this->object, $sysArr);

        $attrIsValidated->setValue($this->object, $arr);

        $this->object->fixErrors();

        self::assertSame(
            ['Servers' => ['1' => ['test' => 'localhost']]],
            $_SESSION['ConfigFile2'],
        );
    }

    /**
     * Test for FormDisplay::validateSelect
     */
    public function testValidateSelect(): void
    {
        $attrValidateSelect = new ReflectionMethod(FormDisplay::class, 'validateSelect');

        $arr = ['foo' => 'var'];
        $value = 'foo';
        self::assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [&$value, $arr],
            ),
        );

        $arr = ['' => 'foobar'];
        $value = '';
        self::assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [&$value, $arr],
            ),
        );
        self::assertIsString($value);

        $arr = ['foobar'];
        $value = 0;
        self::assertTrue(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [&$value, $arr],
            ),
        );

        $arr = ['1' => 'foobar'];
        $value = 0;
        self::assertFalse(
            $attrValidateSelect->invokeArgs(
                $this->object,
                [&$value, $arr],
            ),
        );
    }

    /**
     * Test for FormDisplay::hasErrors
     */
    public function testHasErrors(): void
    {
        self::assertFalse($this->object->hasErrors());

        (new ReflectionProperty(FormDisplay::class, 'errors'))->setValue(
            $this->object,
            [1, 2],
        );

        self::assertTrue($this->object->hasErrors());
    }

    /**
     * Test for FormDisplay::getDocLink
     */
    public function testGetDocLink(): void
    {
        self::assertSame(
            'index.php?route=/url&url='
            . 'https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fconfig.html%23cfg_Servers_3_test_2_',
            $this->object->getDocLink('Servers/3/test/2/'),
        );

        self::assertSame(
            '',
            $this->object->getDocLink('Import'),
        );

        self::assertSame(
            '',
            $this->object->getDocLink('Export'),
        );
    }

    /**
     * Test for FormDisplay::getOptName
     */
    public function testGetOptName(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'getOptName');

        self::assertSame(
            'Servers_',
            $method->invoke($this->object, 'Servers/1/'),
        );

        self::assertSame(
            'Servers_23_',
            $method->invoke($this->object, 'Servers/1/23/'),
        );
    }

    /**
     * Test for FormDisplay::loadUserprefsInfo
     */
    public function testLoadUserprefsInfo(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'loadUserprefsInfo');

        $attrUserprefs = new ReflectionProperty(FormDisplay::class, 'userprefsDisallow');

        $method->invoke($this->object, null);
        self::assertSame(
            [],
            $attrUserprefs->getValue($this->object),
        );
    }

    /**
     * Test for FormDisplay::setComments
     */
    public function testSetComments(): void
    {
        $method = new ReflectionMethod(FormDisplay::class, 'setComments');

        // recoding
        $opts = ['values' => []];
        $opts['values']['iconv'] = 'testIconv';
        $opts['values']['mb'] = 'testMB';
        $opts['comment'] = null;
        $opts['comment_warning'] = null;

        $expect = $opts;

        $method->invokeArgs(
            $this->object,
            ['RecodingEngine', &$opts],
        );

        $expect['comment'] = '';
        if (! function_exists('iconv')) {
            $expect['values']['iconv'] .= ' (unavailable)';
            $expect['comment'] = '"iconv" requires iconv extension';
        }

        $expect['comment_warning'] = 1;

        self::assertEquals($expect, $opts);

        // ZipDump, GZipDump, BZipDump
        $method->invokeArgs(
            $this->object,
            ['ZipDump', &$opts],
        );

        $comment = '';
        if (! function_exists('zip_open')) {
            $comment = 'Compressed import will not work due to missing function zip_open.';
        }

        if (! function_exists('gzcompress')) {
            $comment .= ($comment !== '' ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function gzcompress.';
        }

        self::assertSame($comment, $opts['comment']);

        self::assertTrue($opts['comment_warning']);

        $method->invokeArgs(
            $this->object,
            ['GZipDump', &$opts],
        );

        $comment = '';
        if (! function_exists('gzopen')) {
            $comment = 'Compressed import will not work due to missing function gzopen.';
        }

        if (! function_exists('gzencode')) {
            $comment .= ($comment !== '' ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function gzencode.';
        }

        self::assertSame($comment, $opts['comment']);

        self::assertTrue($opts['comment_warning']);

        $method->invokeArgs(
            $this->object,
            ['BZipDump', &$opts],
        );

        $comment = '';
        if (! function_exists('bzopen')) {
            $comment = 'Compressed import will not work due to missing function bzopen.';
        }

        if (! function_exists('bzcompress')) {
            $comment .= ($comment !== '' ? '; ' : '') . 'Compressed export will not work ' .
            'due to missing function bzcompress.';
        }

        self::assertSame($comment, $opts['comment']);

        self::assertTrue($opts['comment_warning']);

        $config = Config::getInstance();
        $config->setSetup(false);

        $config->settings['MaxDbList'] = 10;
        $config->settings['MaxTableList'] = 10;
        $config->settings['QueryHistoryMax'] = 10;

        $method->invokeArgs(
            $this->object,
            ['MaxDbList', &$opts],
        );

        self::assertSame('maximum 10', $opts['comment']);

        $method->invokeArgs(
            $this->object,
            ['MaxTableList', &$opts],
        );

        self::assertSame('maximum 10', $opts['comment']);

        $method->invokeArgs(
            $this->object,
            ['QueryHistoryMax', &$opts],
        );

        self::assertSame('maximum 10', $opts['comment']);
    }
}
