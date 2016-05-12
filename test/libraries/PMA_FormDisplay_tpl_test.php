<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FromDisplay.tpl.php
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\Theme;

require_once 'libraries/config/FormDisplay.tpl.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/js_escape.lib.php';

/**
 * Tests for FromDisplay.tpl.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_FormDisplay_Tpl_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_displayFormTop()
     *
     * @return void
     */
    public function testDisplayFormTop()
    {
        $_SERVER['REQUEST_URI'] = 'http://www.phpmyadmin.net';
        $GLOBALS['cfg']['ServerDefault'] = '';
        $result = PMA_displayFormTop(null, 'posted', array(1));

        $this->assertContains(
            '<form method="get" action="http://www.phpmyadmin.net" ' .
            'class="config-form disableAjax">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="tab_hash" value="" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="lang" value="en" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="token" value="token" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="0" value="1" />',
            $result
        );
    }

    /**
     * Test for PMA_displayTabsTop()
     *
     * @return void
     */
    public function testDisplayTabsTop()
    {
        $result = PMA_displayTabsTop(array('one', 'two'));

        $this->assertContains(
            '<ul class="tabs"',
            $result
        );

        $this->assertContains(
            '<a href="#0"',
            $result
        );

        $this->assertContains(
            '<a href="#1"',
            $result
        );

        $this->assertContains(
            '<div class="tabs_contents"',
            $result
        );
    }

    /**
     * Test for PMA_displayFieldsetTop()
     *
     * @return void
     */
    public function testDisplayFieldsetTop()
    {
        $attributes = array('name' => 'attrname');
        $errors = array('e1', 'e2');

        $result = PMA_displayFieldsetTop("TitleTest", "DescTest", $errors, $attributes);

        $this->assertContains(
            '<fieldset class="optbox" name="attrname">',
            $result
        );

        $this->assertContains(
            '<legend>',
            $result
        );

        $this->assertContains(
            '<p>',
            $result
        );

        $this->assertContains(
            '<dl class="errors">',
            $result
        );

        $this->assertContains(
            '<dd>',
            $result
        );

        $this->assertContains(
            '<table width="100%" cellspacing="0">',
            $result
        );
    }

    /**
     * Test for PMA_displayInput()
     *
     * @return void
     */
    public function testDisplayInput()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped('Cannot modify constant');
        }

        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemeImage'] = 'testImage';
        $GLOBALS['_FormDislayGroup'] = 1;
        $opts = array();
        $opts['errors'] = array('e1');
        $opts['userprefs_allow'] = false;
        $opts['setvalue'] = ':group';
        $opts['doc'] = "http://doclink";
        $opts['comment'] = "testComment";
        $opts['comment_warning'] = true;
        $opts['show_restore_default'] = true;
        $result = PMA_displayInput(
            'test/path', 'testName', 'text', 'val',
            'desc', false, $opts
        );

        $this->assertContains(
            '<tr class="group-header-field group-header-1 disabled-field">',
            $result
        );

        $this->assertContains(
            '<label for="test/path">',
            $result
        );

        $this->assertContains(
            '<a href="http://doclink" target="documentation"',
            $result
        );

        $this->assertContains(
            '<img src="testImageb_help.png" title="Documentation" ' .
            'alt="Documentation" /',
            $result
        );

        $this->assertContains(
            '<span class="disabled-notice"',
            $result
        );

        $this->assertContains(
            '<small>',
            $result
        );

        $this->assertContains(
            '<input type="text" size="40" name="test/path" id="test/path" ' .
            'class="custom field-error" value="val" />',
            $result
        );

        $this->assertContains(
            '<span class="field-comment-mark field-comment-warning" '
            . 'title="testComment">',
            $result
        );

        $this->assertContains(
            '<a class="restore-default" href="#test/path"',
            $result
        );

        $this->assertContains(
            '<dl class="inline_errors"><dd>e1</dd></dl>',
            $result
        );

        // second case

        define('PMA_SETUP', true);
        $GLOBALS['_FormDislayGroup'] = 0;
        $GLOBALS['cfg']['ThemePath'] = 'themePath';
        $opts = array();
        $opts['errors'] = array();
        $opts['setvalue'] = 'setVal';
        $opts['comment'] = "testComment";
        $opts['show_restore_default'] = true;
        $opts['userprefs_comment'] = 'userprefsComment';
        $opts['userprefs_allow'] = true;

        $result = PMA_displayInput(
            'test/path', 'testName', 'checkbox', 'val',
            '', false, $opts
        );

        $this->assertContains(
            '<tr class="group-field group-field-1">',
            $result
        );

        $this->assertContains(
            '<input type="checkbox" name="test/path" id="test/path" ' .
            'checked="checked" />',
            $result
        );

        $this->assertContains(
            '<a class="userprefs-comment" title="userprefsComment">',
            $result
        );

        $this->assertContains(
            '<td class="userprefs-allow" title="Allow users to customize ' .
            'this value">',
            $result
        );

        $this->assertContains(
            '<a class="set-value" href="#test/path=setVal" ' .
            'title="Set value: setVal" style="display:none">',
            $result
        );

        // short_text
        $GLOBALS['_FormDislayGroup'] = 0;
        $GLOBALS['cfg']['ThemePath'] = 'themePath';
        $opts = array();
        $opts['errors'] = array();

        $result = PMA_displayInput(
            'test/path', 'testName', 'short_text', 'val',
            '', true, $opts
        );

        $this->assertContains(
            '<input type="text" size="25" name="test/path" id="test/path" ' .
            'value="val" />',
            $result
        );

        // number_text
        $result = PMA_displayInput(
            'test/path', 'testName', 'number_text', 'val',
            '', true, $opts
        );

        $this->assertContains(
            '<input type="number" name="test/path" ' .
            'id="test/path" value="val" />',
            $result
        );

        // select case 1
        $opts['values_escaped'] = true;
        $opts['values_disabled'] = array(1, 2);
        $opts['values'] = array(
            1 => 'test',
            'key1' => true,
            'key2' => false,
        );
        $result = PMA_displayInput(
            'test/path', 'testName', 'select', true,
            '', true, $opts
        );
        $this->assertContains(
            '<select name="test/path" id="test/path">',
            $result
        );

        $this->assertContains(
            '<option value="1" selected="selected" disabled="disabled">',
            $result
        );

        $this->assertContains(
            '<option value="key1">',
            $result
        );

        $this->assertContains(
            '<option value="key2">',
            $result
        );

        // select case 2
        $opts['values_escaped'] = false;
        $opts['values_disabled'] = array(1, 2);
        $opts['values'] = array(
            'a<b' => 'c&d',
            'key1' => true,
            'key2' => false,
        );
        $result = PMA_displayInput(
            'test/path', 'testName', 'select', false,
            '', true, $opts
        );

        $this->assertContains(
            '<select name="test/path" id="test/path">',
            $result
        );

        // assertContains doesn't seem to work with htmlentities
        $this->assertContains(
            '<option value="a&lt;b">c&amp;d</option>',
            $result
        );

        // list
        $result = PMA_displayInput(
            'test/path', 'testName', 'list', array('foo', 'bar'),
            '', true, $opts
        );

        $this->assertContains(
            '<textarea cols="40" rows="5" name="test/path" id="test/path">',
            $result
        );
        runkit_constant_remove('PMA_SETUP');
    }

    /**
     * Test for PMA_displayGroupHeader()
     *
     * @return void
     */
    public function testDisplayGroupHeader()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped('Cannot modify constant');
        }

        $this->assertNull(
            PMA_displayGroupHeader('')
        );

        $GLOBALS['_FormDisplayGroup'] = 3;

        if (!defined('PMA_SETUP')) {
            define('PMA_SETUP', true);
        }

        $result = PMA_displayGroupHeader('headerText');

        $this->assertContains(
            '<tr class="group-header group-header-4">',
            $result
        );

        // without PMA_SETUP

        runkit_constant_remove('PMA_SETUP');
        $GLOBALS['_FormDisplayGroup'] = 3;

        $result = PMA_displayGroupHeader('headerText');

        $this->assertContains(
            '<tr class="group-header group-header-4">',
            $result
        );

    }

    /**
     * Test for PMA_displayGroupFooter()
     *
     * @return void
     */
    public function testDisplayGroupFooter()
    {
        $GLOBALS['_FormDisplayGroup'] = 3;
        PMA_displayGroupFooter();
        $this->assertEquals(
            2,
            $GLOBALS['_FormDisplayGroup']
        );
    }

    /**
     * Test for PMA_displayFieldsetBottom()
     *
     * @return void
     */
    public function testDisplayFieldsetBottom()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped('Cannot modify constant');
        }

        // with PMA_SETUP

        if (!defined('PMA_SETUP')) {
            define('PMA_SETUP', true);
        }

        $result = PMA_displayFieldsetBottom();

        $this->assertContains(
            '<td colspan="3" class="lastrow">',
            $result
        );

        $this->assertContains(
            '<input type="submit" name="submit_save" value="Apply"',
            $result
        );

        $this->assertContains(
            '<input type="button" name="submit_reset" value="Reset" />',
            $result
        );

        $this->assertContains(
            '</fieldset>',
            $result
        );

        // without PMA_SETUP

        runkit_constant_remove('PMA_SETUP');

        $result = PMA_displayFieldsetBottom();

        $this->assertContains(
            '<td colspan="2" class="lastrow">',
            $result
        );
    }

    /**
     * Test for PMA_displayFieldsetBottomSimple()
     *
     * @return void
     */
    public function testDisplayFieldsetBottomSimple()
    {
        $result = PMA_displayFieldsetBottomSimple();
        $this->assertEquals(
            '</table></fieldset>',
            $result
        );
    }

    /**
     * Test for PMA_displayTabsBottom()
     *
     * @return void
     */
    public function testDisplayTabsBottom()
    {
        $result = PMA_displayTabsBottom();
        $this->assertEquals(
            "</div>\n",
            $result
        );
    }

    /**
     * Test for PMA_displayFormBottom()
     *
     * @return void
     */
    public function testDisplayFormBottom()
    {
        $result = PMA_displayFormBottom();
        $this->assertEquals(
            "</form>\n",
            $result
        );
    }

    /**
     * Test for PMA_addJsValidate()
     *
     * @return void
     */
    public function testAddJsValidate()
    {
        $validators = array(
            'one' => array('\\\';', '\r\n\\\'<scrIpt></\' + \'script>'),
            'two' => array()
        );

        $js = array();

        PMA_addJsValidate('testID', $validators, $js);

        $this->assertEquals(
            array(
                'validateField(\'testID\', \'PMA_\\\';\', true, '
                    . '[\'\\\\r\\\\n\\\\\\\''
                    . '<scrIpt></\\\' + \\\'script>\'])',
                'validateField(\'testID\', \'PMA_\', true)'
            ),
            $js
        );
    }

    /**
     * Test for PMA_displayJavascript()
     *
     * @return void
     */
    public function testDisplayJavascript()
    {
        $this->assertNull(
            PMA_displayJavascript(array())
        );

        $result = PMA_displayJavascript(array('var i = 1', 'i++'));

        $this->assertEquals(
            '<script type="text/javascript">' . "\n"
            . 'if (typeof configInlineParams === "undefined"'
            . ' || !Array.isArray(configInlineParams)) '
            . 'configInlineParams = [];' . "\n"
            . 'configInlineParams.push(function() {' . "\n"
            . 'var i = 1;' . "\n"
            . 'i++;' . "\n"
            . '});' . "\n"
            . 'if (typeof configScriptLoaded !== "undefined"'
            . ' && configInlineParams) loadInlineConfig();'
            . "\n" . '</script>',
            $result
        );
    }

    /**
     * Test for PMA_displayErrors()
     *
     * @return void
     */
    public function testDisplayErrors()
    {
        $errors = array('<err1>', '&err2');

        $result = PMA_displayErrors('err"Name1"', $errors);

        $this->assertEquals(
            '<dl><dt>err&quot;Name1&quot;</dt>' .
            '<dd>&lt;err1&gt;</dd><dd>&amp;err2</dd></dl>',
            $result
        );

    }
}
