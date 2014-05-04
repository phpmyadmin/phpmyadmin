<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for FromDisplay.tpl.php
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/FormDisplay.tpl.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
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
        ob_start();
        PMA_displayFormTop(null, 'posted', array(1));
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<form method="get" action="http://www.phpmyadmin.net" ' .
                'class="config-form disableAjax">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="tab_hash" value="" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="check_page_refresh"  ' .
                'id="check_page_refresh" value="" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="lang" value="en" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="token" value="token" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="hidden" name="0" value="1" />'
            ),
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
        ob_start();
        PMA_displayTabsTop(array('one', 'two'));
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray('<ul class="tabs">'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<a href="#0">', array('content' => 'one')),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<a href="#1">', array('content' => 'two')),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<div class="tabs_contents">'),
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

        ob_start();
        PMA_displayFieldsetTop("TitleTest", "DescTest", $errors, $attributes);
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<fieldset class="optbox" name="attrname">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<legend>',
                array(
                    'content' => 'TitleTest',
                    'parent' => array('tag' => 'fieldset')
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<p>',
                array(
                    'content' => 'DescTest',
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<dl class="errors">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<dd>',
                array('content' => 'e1', 'parent' => array('tag' => 'dl'))
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<dd>',
                array('content' => 'e2', 'parent' => array('tag' => 'dl'))
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<table width="100%" cellspacing="0">'
            ),
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

        $_SESSION['PMA_Theme'] = new PMA_Theme();
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
        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'text', 'val',
            'desc', false, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<tr class="group-header-field group-header-1 disabled-field">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<label for="test/path">',
                array('content' => 'testName')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a href="http://doclink" target="documentation">',
                array(
                    'parent' => array(
                        'tag' => 'span',
                        'attributes' => array('class' => 'doc')
                    )
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<img src="testImageb_help.png" title="Documentation" ' .
                'alt="Documentation" />',
                array(
                    'parent' => array('tag' => 'a')
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<span class="disabled-notice">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<small>',
                array('content' => 'desc')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="text" size="60" name="test/path" id="test/path" ' .
                'class="custom field-error" value="val" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<span class="field-comment-mark field-comment-warning" '
                . 'title="testComment">',
                array('content' => 'i')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a class="restore-default" href="#test/path" ' .
                'style="display:none">'
            ),
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

        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'checkbox', 'val',
            '', false, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<tr class="group-field group-field-1">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="checkbox" name="test/path" id="test/path" ' .
                'checked="checked" />',
                array(
                    'parent' => array(
                        'tag' => 'span',
                        'attributes' => array('class' => 'checkbox custom')
                    )
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a class="userprefs-comment" title="userprefsComment">',
                array('child' => array('tag' => 'img'))
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<td class="userprefs-allow" title="Allow users to customize ' .
                'this value">',
                array(
                    'child' => PMA_getTagArray(
                        '<input type="checkbox" name="test/path-userprefs-allow" ' .
                        'checked="checked"/>'
                    )
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a class="set-value" href="#test/path=setVal" ' .
                'title="Set value: setVal" style="display:none">',
                array('child' => array('tag' => 'img'))
            ),
            $result
        );

        // short_text
        $GLOBALS['_FormDislayGroup'] = 0;
        $GLOBALS['cfg']['ThemePath'] = 'themePath';
        $opts = array();
        $opts['errors'] = array();

        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'short_text', 'val',
            '', true, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<input type="text" size="25" name="test/path" id="test/path" ' .
                'value="val" />'
            ),
            $result
        );

        // number_text
        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'number_text', 'val',
            '', true, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<input type="number" name="test/path" ' .
                'id="test/path" value="val" />'
            ),
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
        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'select', true,
            '', true, $opts
        );
        $result = ob_get_clean();
        $this->assertTag(
            PMA_getTagArray(
                '<select name="test/path" id="test/path">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<select name="test/path" id="test/path">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<option value="1" selected="selected" disabled="disabled">',
                array(
                    'parent' => array('tag' => 'select'),
                    'content' => "test"
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<option value="key1">',
                array(
                    'parent' => array('tag' => 'select'),
                    'content' => "yes"
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<option value="key2">',
                array(
                    'parent' => array('tag' => 'select'),
                    'content' => "no"
                )
            ),
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
        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'select', false,
            '', true, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<select name="test/path" id="test/path">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<select name="test/path" id="test/path">'
            ),
            $result
        );

        // assertTag doesn't seem to work with htmlentities
        $this->assertContains(
            '<option value="a&lt;b">c&amp;d</option>',
            $result
        );

        // list

        ob_start();
        PMA_displayInput(
            'test/path', 'testName', 'list', array('foo', 'bar'),
            '', true, $opts
        );
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<textarea cols="40" rows="5" name="test/path" id="test/path">',
                array(
                    'content' => "foo\nbar"
                )
            ),
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

        ob_start();
        PMA_displayGroupHeader('headerText');
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<tr class="group-header group-header-4">',
                array(
                    'child' => PMA_getTagArray(
                        '<th colspan="3">',
                        array(
                            'content' => 'headerText'
                        )
                    )
                )
            ),
            $result
        );

        // without PMA_SETUP

        runkit_constant_remove('PMA_SETUP');
        $GLOBALS['_FormDisplayGroup'] = 3;

        ob_start();
        PMA_displayGroupHeader('headerText');
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<tr class="group-header group-header-4">',
                array(
                    'child' => PMA_getTagArray(
                        '<th colspan="2">',
                        array(
                            'content' => 'headerText'
                        )
                    )
                )
            ),
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

        ob_start();
        PMA_displayFieldsetBottom();
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<td colspan="3" class="lastrow">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="submit" name="submit_save" value="Apply"',
                array(
                    'parent' => array('tag' => 'td')
                )
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="button" name="submit_reset" value="Reset" />',
                array(
                    'parent' => array('tag' => 'td')
                )
            ),
            $result
        );

        $this->assertContains(
            '</fieldset>',
            $result
        );

        // without PMA_SETUP

        runkit_constant_remove('PMA_SETUP');

        ob_start();
        PMA_displayFieldsetBottom();
        $result = ob_get_clean();

        $this->assertTag(
            PMA_getTagArray(
                '<td colspan="2" class="lastrow">'
            ),
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
        $this->expectOutputString(
            '</table></fieldset>'
        );
        PMA_displayFieldsetBottomSimple();
    }

    /**
     * Test for PMA_displayTabsBottom()
     *
     * @return void
     */
    public function testDisplayTabsBottom()
    {
        $this->expectOutputString(
            "</div>\n"
        );
        PMA_displayTabsBottom();
    }

    /**
     * Test for PMA_displayFormBottom()
     *
     * @return void
     */
    public function testDisplayFormBottom()
    {
        $this->expectOutputString(
            "</form>\n"
        );
        PMA_displayFormBottom();
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

        $this->expectOutputString(
            "<script type=\"text/javascript\">\n" .
            "var i = 1;\n" .
            "i++;\n" .
            "</script>\n"
        );

        PMA_displayJavascript(array('var i = 1', 'i++'));
    }

    /**
     * Test for PMA_displayErrors()
     *
     * @return void
     */
    public function testDisplayErrors()
    {
        $errors = array('<err1>', '&err2');

        $this->expectOutputString(
            '<dl><dt>err&quot;Name1&quot;</dt>' .
            '<dd>&lt;err1&gt;</dd><dd>&amp;err2</dd></dl>'
        );

        PMA_displayErrors('err"Name1"', $errors);

    }
}
?>
