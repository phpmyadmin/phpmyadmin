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

        $this->assertTag(
            PMA_getTagArray('<select class="pageselector ajax" name="pos">'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<option selected="selected" '
                . 'style="font-weight: bold" value="0">',
                array('content' => '1')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<option value="25">',
                array('content' => '2')
            ),
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

        $this->assertTag(
            PMA_getTagArray('<td>'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a class="foreign_value" href="#" '
                . 'title="Use this value">',
                array('content' => 'foo')
            ),
            $result
        );

        $cssClass = 'baz';
        $isSelected = true;
        $keyname = 'bar';
        $title = 'foo';
        $result = PMA_getHtmlForColumnElement(
            $cssClass, $isSelected, $keyname,
            $description, $title
        );

        $this->assertTag(
            PMA_getTagArray('<td>'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<strong>'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<a class="foreign_value" href="#" '
                . 'title="Use this value: foo">',
                array('content' => 'bar')
            ),
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
        $data = array();
        $_REQUEST['rownumber'] = 1;
        $_REQUEST['foreign_filter'] = '5';
        $result = PMA_getHtmlForRelationalFieldSelection(
            $db, $table, $field, $foreignData, $fieldkey, $data
        );

        $this->assertTag(
            PMA_getTagArray(
                '<form class="ajax" '
                . 'id="browse_foreign_form" name="browse_foreign_from" '
                . 'action="browse_foreigners.php" method="post">'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<fieldset>'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<input type="hidden" name="field" value="foo" />'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<input type="hidden" name="fieldkey" value="bar" />'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<input type="hidden" name="rownumber" value="1" />'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<span class="formelement">'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<label for="input_foreign_filter">',
                array('content' => 'Search:')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="text" name="foreign_filter" '
                . 'id="input_foreign_filter" '
                . 'value="5" data-old="5" '
                . '/>'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<input type="submit" name="submit_foreign_filter" value="Go" />'
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<span class="formelement">',
                array('content' => '')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray('<table width="100%" id="browse_foreign_table">'),
            $result
        );

        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;
        $GLOBALS['cfg']['ShowAll'] = false;
        $result = PMA_getHtmlForRelationalFieldSelection(
            $db, $table, $field, $foreignData, $fieldkey, $data
        );

        $this->assertTag(
            PMA_getTagArray('<table width="100%" id="browse_foreign_table">'),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<th>',
                array('content' => 'Keyname')
            ),
            $result
        );

        $this->assertTag(
            PMA_getTagArray(
                '<th>',
                array('content' => 'Description')
            ),
            $result
        );
    }
}
?>
