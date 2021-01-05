<?php
/**
 * tests for FormDisplayTemplate
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for FormDisplayTemplate
 */
class FormDisplayTemplateTest extends AbstractTestCase
{
    /** @var FormDisplayTemplate */
    protected $formDisplayTemplate;

    /** @var Config */
    protected $config;

    /**
     * Setup tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
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
            $opts
        );

        $this->assertStringContainsString(
            '<tr class="group-header-field group-header-1 disabled-field">',
            $result
        );

        $this->assertStringContainsString(
            '<label for="test/path">',
            $result
        );

        $this->assertStringContainsString(
            '<a href="https://example.com/" target="documentation"',
            $result
        );

        $this->assertStringContainsString(
            '<img src="themes/dot.gif" title="Documentation" ' .
            'alt="Documentation" class="icon ic_b_help"',
            $result
        );

        $this->assertStringContainsString(
            '<span class="disabled-notice"',
            $result
        );

        $this->assertStringContainsString(
            '<small>',
            $result
        );

        $this->assertStringContainsString(
            '<input type="text" name="test/path" id="test/path" value="val"' .
            ' class="w-75 custom field-error">',
            $result
        );

        $this->assertStringContainsString(
            '<a class="restore-default hide" href="#test/path"',
            $result
        );

        $this->assertStringContainsString('<dl class="inline_errors">', $result);
        $this->assertStringContainsString('<dd>e1</dd>', $result);
        $this->assertStringContainsString('</dl>', $result);

        // second case

        $this->config->set('is_setup', true);
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
            $opts
        );

        $this->assertStringContainsString(
            '<tr class="group-field group-field-1">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="checkbox" name="test/path" id="test/path" ' .
            'checked>',
            $result
        );

        $this->assertStringContainsString(
            '<a class="userprefs-comment" title="userprefsComment">',
            $result
        );

        $this->assertStringContainsString(
            '<td class="userprefs-allow" title="Allow users to customize ' .
            'this value">',
            $result
        );

        $this->assertStringContainsString(
            '<a class="set-value hide" href="#test/path=setVal" ' .
            'title="Set value: setVal">',
            $result
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
            $opts
        );

        $this->assertStringContainsString(
            '<input type="text" size="25" name="test/path" id="test/path" ' .
            'value="val" class="">',
            $result
        );

        // number_text
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'number_text',
            'val',
            '',
            true,
            $opts
        );

        $this->assertStringContainsString(
            '<input type="number" name="test/path" ' .
            'id="test/path" value="val" class="">',
            $result
        );

        // select case 1
        $opts['values_escaped'] = true;
        $opts['values_disabled'] = [
            1,
            2,
        ];
        $opts['values'] = [
            1 => 'test',
            'key1' => true,
            'key2' => false,
        ];
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'select',
            true,
            '',
            true,
            $opts
        );
        $this->assertStringContainsString(
            '<select name="test/path" id="test/path" class="w-75">',
            $result
        );

        $this->assertStringContainsString(
            '<option value="1" selected disabled>',
            $result
        );

        $this->assertStringContainsString(
            '<option value="key1">',
            $result
        );

        $this->assertStringContainsString(
            '<option value="key2">',
            $result
        );

        // select case 2
        $opts['values_escaped'] = false;
        $opts['values_disabled'] = [
            1,
            2,
        ];
        $opts['values'] = [
            'a<b' => 'c&d',
            'key1' => true,
            'key2' => false,
        ];
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'select',
            false,
            '',
            true,
            $opts
        );

        $this->assertStringContainsString(
            '<select name="test/path" id="test/path" class="w-75">',
            $result
        );

        // assertContains doesn't seem to work with htmlentities
        $this->assertStringContainsString(
            '<option value="a&lt;b">c&amp;d</option>',
            $result
        );

        // list
        $result = $this->formDisplayTemplate->displayInput(
            'test/path',
            'testName',
            'list',
            [
                'foo',
                'bar',
            ],
            '',
            true,
            $opts
        );

        $this->assertStringContainsString(
            '<textarea cols="35" rows="5" name="test/path" id="test/path" class="">',
            $result
        );
    }

    /**
     * Test for displayGroupHeader()
     */
    public function testDisplayGroupHeader(): void
    {
        $this->assertEquals(
            '',
            $this->formDisplayTemplate->displayGroupHeader('')
        );

        $this->formDisplayTemplate->group = 3;

        $this->config->set('is_setup', true);

        $result = $this->formDisplayTemplate->displayGroupHeader('headerText');

        $this->assertStringContainsString(
            '<tr class="group-header group-header-4">',
            $result
        );

        // without PMA_SETUP
        $this->config->set('is_setup', false);

        $this->formDisplayTemplate->group = 3;

        $result = $this->formDisplayTemplate->displayGroupHeader('headerText');

        $this->assertStringContainsString(
            '<tr class="group-header group-header-4">',
            $result
        );
    }

    /**
     * Test for displayGroupFooter()
     */
    public function testDisplayGroupFooter(): void
    {
        $this->formDisplayTemplate->group = 3;
        $this->formDisplayTemplate->displayGroupFooter();
        $this->assertEquals(
            2,
            $this->formDisplayTemplate->group
        );
    }

    /**
     * Test for addJsValidate()
     */
    public function testAddJsValidate(): void
    {
        $validators = [
            'one' => [
                '\\\';',
                '\r\n\\\'<scrIpt></\' + \'script>',
            ],
            'two' => [],
        ];

        $js = [];

        $this->formDisplayTemplate->addJsValidate('testID', $validators, $js);

        $this->assertEquals(
            [
                'registerFieldValidator(\'testID\', \'\\\';\', true, '
                . '[\'\\\\r\\\\n\\\\\\\''
                . '<scrIpt></\\\' + \\\'script>\'])',
                'registerFieldValidator(\'testID\', \'\', true)',
            ],
            $js
        );
    }
}
