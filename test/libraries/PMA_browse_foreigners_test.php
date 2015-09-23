<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/browse_foreigners.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/browse_foreigners.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';

/**
 * Tests for libraries/browse_foreigners.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_BrowseForeignersTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['MaxRows'] = 25;
    }

    /**
     * Test for PMA_getForeignLimit
     *
     * @return void
     */
    function testGetForeignLimit()
    {
        $this->assertNull(
            PMA_getForeignLimit('Show all')
        );

        $this->assertEquals(
            'LIMIT 0, 25 ',
            PMA_getForeignLimit(null)
        );

        $_REQUEST['pos'] = 10;

        $this->assertEquals(
            'LIMIT 10, 25 ',
            PMA_getForeignLimit(null)
        );

        $GLOBALS['cfg']['MaxRows'] = 50;

        $this->assertEquals(
            'LIMIT 10, 50 ',
            PMA_getForeignLimit(null)
        );

        $this->assertEquals(
            'LIMIT 10, 50 ',
            PMA_getForeignLimit('xyz')
        );
    }

    /**
     * Test for PMA_getHtmlForShowAll
     *
     * @return void
     */
    function testGetHtmlForShowAll()
    {
        $this->assertEquals(
            '',
            PMA_getHtmlForShowAll(null)
        );

        $foreignData = array();
        $foreignData['disp_row'] = array();
        $GLOBALS['cfg']['ShowAll'] = false;

        $this->assertEquals(
            '',
            PMA_getHtmlForShowAll($foreignData)
        );

        $GLOBALS['cfg']['ShowAll'] = true;
        $foreignData['the_total'] = 0;

        $this->assertEquals(
            '',
            PMA_getHtmlForShowAll($foreignData)
        );

        $foreignData['the_total'] = 30;

        $this->assertEquals(
            '<input type="submit" id="foreign_showAll" '
            . 'name="foreign_showAll" '
            . 'value="' . 'Show all' . '" />',
            PMA_getHtmlForShowAll($foreignData)
        );
    }

    /**
     * Test for PMA_getHtmlForGotoPage
     *
     * @return void
     */
    function testGetHtmlForGotoPage()
    {
        $this->assertEquals(
            '',
            PMA_getHtmlForGotoPage(null)
        );

        $_REQUEST['pos'] = 15;
        $foreignData = array();
        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;

        $this->assertEquals(
            '',
            PMA_getHtmlForGotoPage($foreignData)
        );

        $foreignData['the_total'] = 30;
        $result = PMA_getHtmlForGotoPage($foreignData);

        $this->assertStringStartsWith(
            'Page number:',
            $result
        );

        $this->assertStringEndsWith(
            '</select>',
            $result
        );

        $this->assertContains(
            '<select class="pageselector ajax" name="pos"',
            $result
        );

        $this->assertContains(
            '<option selected="selected" '
            . 'style="font-weight: bold" value="0">',
            $result
        );

        $this->assertContains(
            '<option  value="25"',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnElement
     *
     * @return void
     */
    function testGetHtmlForColumnElement()
    {
        $cssClass = '';
        $isSelected = false;
        $keyname = '';
        $description = 'foo';
        $title = '';
        $result = PMA_getHtmlForColumnElement(
            $cssClass, $isSelected, $keyname,
            $description, $title
        );

        $this->assertContains(
            '<td>',
            $result
        );

        $this->assertContains(
            '<a class="foreign_value" data-key="" href="#" '
            . 'title="Use this value">',
            $result
        );

        $cssClass = 'class="baz"';
        $isSelected = true;
        $keyname = 'bar';
        $title = 'foo';
        $result = PMA_getHtmlForColumnElement(
            $cssClass, $isSelected, $keyname,
            $description, $title
        );

        $this->assertContains(
            '<td class="baz">',
            $result
        );

        $this->assertContains(
            '<strong>',
            $result
        );

        $this->assertContains(
            '<a class="foreign_value" data-key="bar" href="#" '
            . 'title="Use this value: foo">',
            $result
        );
    }

    /**
     * Test for PMA_getDescriptionAndTitle
     *
     * @return void
     */
    function testGetDescriptionAndTitle()
    {
        $GLOBALS['cfg']['LimitChars'] = 30;
        $desc = 'foobar<baz';

        $this->assertEquals(
            array('foobar&lt;baz', ''),
            PMA_getDescriptionAndTitle($desc)
        );

        $GLOBALS['cfg']['LimitChars'] = 5;

        $this->assertEquals(
            array('fooba...', 'foobar&lt;baz'),
            PMA_getDescriptionAndTitle($desc)
        );
    }

    /**
     * Test for PMA_getHtmlForRelationalFieldSelection
     *
     * @return void
     */
    function testGetHtmlForRelationalFieldSelection()
    {
        $db = '';
        $table = '';
        $field = 'foo';
        $foreignData = array();
        $foreignData['disp_row'] = '';
        $fieldkey = 'bar';
        $current_value = '';
        $_REQUEST['rownumber'] = 1;
        $_REQUEST['foreign_filter'] = '5';
        $result = PMA_getHtmlForRelationalFieldSelection(
            $db, $table, $field, $foreignData, $fieldkey, $current_value
        );

        $this->assertContains(
            '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" '
            . 'action="browse_foreigners.php" method="post">',
            $result
        );

        $this->assertContains(
            '<fieldset>',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="field" value="foo" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="fieldkey" value="bar" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="rownumber" value="1" />',
            $result
        );

        $this->assertContains(
            '<span class="formelement">',
            $result
        );

        $this->assertContains(
            '<label for="input_foreign_filter">',
            $result
        );

        $this->assertContains(
            '<input type="text" name="foreign_filter" '
            . 'id="input_foreign_filter" '
            . 'value="5" data-old="5" '
            . '/>',
            $result
        );

        $this->assertContains(
            '<input type="submit" name="submit_foreign_filter" value="Go" />',
            $result
        );

        $this->assertContains(
            '<span class="formelement">',
            $result
        );

        $this->assertContains(
            '<table width="100%" id="browse_foreign_table">',
            $result
        );

        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;
        $GLOBALS['cfg']['ShowAll'] = false;
        $result = PMA_getHtmlForRelationalFieldSelection(
            $db, $table, $field, $foreignData, $fieldkey, $current_value
        );

        $this->assertContains(
            '<table width="100%" id="browse_foreign_table">',
            $result
        );

        $this->assertContains(
            '<th>',
            $result
        );

    }
}
