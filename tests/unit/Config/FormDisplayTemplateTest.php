<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FormDisplayTemplate::class)]
class FormDisplayTemplateTest extends AbstractTestCase
{
    protected FormDisplayTemplate $formDisplayTemplate;

    protected Config $config;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->config = new Config();
        $this->formDisplayTemplate = new FormDisplayTemplate($this->config);
    }

    /**
     * Test for displayInput()
     */
    public function testDisplayInput(): void
    {
        $opts = [];
        $opts['errors'] = ['e1'];
        $opts['userprefs_allow'] = false;
        $opts['setvalue'] = ':group';
        $opts['doc'] = 'https://example.com/';
        $opts['comment'] = 'testComment';
        $opts['comment_warning'] = true;
        $opts['show_restore_default'] = true;
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'text',
            'val',
            'desc',
            false,
            $opts,
        );

        self::assertStringContainsString('<tr class="group-header-field group-header-1 disabled-field">', $result);

        self::assertStringContainsString('<label for="test/path">', $result);

        self::assertStringContainsString('<a href="https://example.com/" target="documentation"', $result);

        self::assertStringContainsString(
            '<img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"',
            $result,
        );

        self::assertStringContainsString('<span class="disabled-notice"', $result);

        self::assertStringContainsString('<small>', $result);

        self::assertStringContainsString(
            '<input type="text" name="test/path" id="test/path" value="val" class="w-75 custom field-error">',
            $result,
        );

        self::assertStringContainsString('<a class="restore-default hide" href="#test/path"', $result);

        self::assertStringContainsString('<dl class="inline_errors">', $result);
        self::assertStringContainsString('<dd>e1</dd>', $result);
        self::assertStringContainsString('</dl>', $result);

        // second case

        $this->config->setSetup(true);
        $opts = [];
        $opts['errors'] = [];
        $opts['setvalue'] = 'setVal';
        $opts['comment'] = 'testComment';
        $opts['show_restore_default'] = true;
        $opts['userprefs_comment'] = 'userprefsComment';
        $opts['userprefs_allow'] = true;

        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'checkbox',
            'val',
            '',
            false,
            $opts,
        );

        self::assertStringContainsString('<tr class="group-field group-field-1">', $result);

        self::assertStringContainsString('<input type="checkbox" name="test/path" id="test/path" checked>', $result);

        self::assertStringContainsString('<a class="userprefs-comment" title="userprefsComment">', $result);

        self::assertStringContainsString(
            '<td class="userprefs-allow" title="Allow users to customize this value">',
            $result,
        );

        self::assertStringContainsString(
            '<a class="set-value hide" href="#test/path=setVal" title="Set value: setVal">',
            $result,
        );

        // short_text
        $opts = [];
        $opts['errors'] = [];

        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'short_text',
            'val',
            '',
            true,
            $opts,
        );

        self::assertStringContainsString(
            '<input type="text" size="25" name="test/path" id="test/path" value="val" class="">',
            $result,
        );

        // number_text
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'number_text',
            'val',
            '',
            true,
            $opts,
        );

        self::assertStringContainsString(
            '<input type="number" name="test/path" id="test/path" value="val" class="">',
            $result,
        );

        // select case 1
        $opts['values_escaped'] = true;
        $opts['values_disabled'] = [1, 2];
        $opts['values'] = [1 => 'test', 'key1' => true, 'key2' => false];
        $result = $this->formDisplayTemplate->displayInput('test/path', 'testName', 'select', true, '', true, $opts);
        self::assertStringContainsString('<select name="test/path" id="test/path" class="w-75">', $result);

        self::assertStringContainsString('<option value="1" selected disabled>', $result);

        self::assertStringContainsString('<option value="key1">', $result);

        self::assertStringContainsString('<option value="key2">', $result);

        // select case 2
        $opts['values_escaped'] = false;
        $opts['values_disabled'] = [1, 2];
        $opts['values'] = ['a<b' => 'c&d', 'key1' => true, 'key2' => false];
        $result = $this->formDisplayTemplate->displayInput('test/path', 'testName', 'select', false, '', true, $opts);

        self::assertStringContainsString('<select name="test/path" id="test/path" class="w-75">', $result);

        // assertContains doesn't seem to work with htmlentities
        self::assertStringContainsString('<option value="a&lt;b">c&amp;d</option>', $result);

        // list
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'list',
            ['foo', 'bar'],
            '',
            true,
            $opts,
        );

        self::assertStringContainsString(
            '<textarea cols="35" rows="5" name="test/path" id="test/path" class="">',
            $result,
        );
    }

    /**
     * Test for displayGroupHeader()
     */
    public function testDisplayGroupHeader(): void
    {
        self::assertSame(
            '',
            $this->formDisplayTemplate->displayGroupHeader(''),
        );

        $this->formDisplayTemplate->group = 3;

        $this->config->setSetup(true);

        $result = $this->formDisplayTemplate->displayGroupHeader('headerText');

        self::assertStringContainsString('<tr class="group-header group-header-4">', $result);

        // without PMA_SETUP
        $this->config->setSetup(false);

        $this->formDisplayTemplate->group = 3;

        $result = $this->formDisplayTemplate->displayGroupHeader('headerText');

        self::assertStringContainsString('<tr class="group-header group-header-4">', $result);
    }

    /**
     * Test for displayGroupFooter()
     */
    public function testDisplayGroupFooter(): void
    {
        $this->formDisplayTemplate->group = 3;
        $this->formDisplayTemplate->displayGroupFooter();
        self::assertSame(2, $this->formDisplayTemplate->group);
    }

    /**
     * Test for addJsValidate()
     */
    public function testAddJsValidate(): void
    {
        $validators = ['one' => ['\\\';', '\r\n\\\'<scrIpt></\' + \'script>'], 'two' => []];

        $js = [];

        $this->formDisplayTemplate->addJsValidate('testID', $validators, $js);

        self::assertSame(
            [
                ['fieldId' => 'testID', 'name' => '\\\';', 'args' => ['\r\n\\\'<scrIpt></\' + \'script>']],
                ['fieldId' => 'testID', 'name' => null, 'args' => null],
            ],
            $js,
        );
    }
}
