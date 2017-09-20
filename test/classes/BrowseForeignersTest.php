<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\BrowseForeigners
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\BrowseForeigners;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Tests for PhpMyAdmin\BrowseForeigners
 *
 * @package PhpMyAdmin-test
 */
class BrowseForeignersTest extends TestCase
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
     * Test for BrowseForeigners::getForeignLimit
     *
     * @return void
     */
    function testGetForeignLimit()
    {
        $this->assertNull(
            BrowseForeigners::getForeignLimit('Show all')
        );

        $this->assertEquals(
            'LIMIT 0, 25 ',
            BrowseForeigners::getForeignLimit(null)
        );

        $_REQUEST['pos'] = 10;

        $this->assertEquals(
            'LIMIT 10, 25 ',
            BrowseForeigners::getForeignLimit(null)
        );

        $GLOBALS['cfg']['MaxRows'] = 50;

        $this->assertEquals(
            'LIMIT 10, 50 ',
            BrowseForeigners::getForeignLimit(null)
        );

        $this->assertEquals(
            'LIMIT 10, 50 ',
            BrowseForeigners::getForeignLimit('xyz')
        );
    }

    /**
     * Test for BrowseForeigners::getHtmlForShowAll
     *
     * @return void
     */
    function testGetHtmlForShowAll()
    {
        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForShowAll(null)
        );

        $foreignData = array();
        $foreignData['disp_row'] = array();
        $GLOBALS['cfg']['ShowAll'] = false;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForShowAll($foreignData)
        );

        $GLOBALS['cfg']['ShowAll'] = true;
        $foreignData['the_total'] = 0;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForShowAll($foreignData)
        );

        $foreignData['the_total'] = 30;

        $this->assertEquals(
            '<input type="submit" id="foreign_showAll" '
            . 'name="foreign_showAll" '
            . 'value="' . 'Show all' . '" />',
            BrowseForeigners::getHtmlForShowAll($foreignData)
        );
    }

    /**
     * Test for BrowseForeigners::getHtmlForGotoPage
     *
     * @return void
     */
    function testGetHtmlForGotoPage()
    {
        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForGotoPage(null)
        );

        $_REQUEST['pos'] = 15;
        $foreignData = array();
        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForGotoPage($foreignData)
        );

        $foreignData['the_total'] = 30;
        $result = BrowseForeigners::getHtmlForGotoPage($foreignData);

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
     * Test for BrowseForeigners::getHtmlForColumnElement
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
        $result = BrowseForeigners::getHtmlForColumnElement(
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
        $result = BrowseForeigners::getHtmlForColumnElement(
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
     * Test for BrowseForeigners::getDescriptionAndTitle
     *
     * @return void
     */
    function testGetDescriptionAndTitle()
    {
        $GLOBALS['cfg']['LimitChars'] = 30;
        $desc = 'foobar<baz';

        $this->assertEquals(
            array('foobar&lt;baz', ''),
            BrowseForeigners::getDescriptionAndTitle($desc)
        );

        $GLOBALS['cfg']['LimitChars'] = 5;

        $this->assertEquals(
            array('fooba...', 'foobar&lt;baz'),
            BrowseForeigners::getDescriptionAndTitle($desc)
        );
    }

    /**
     * Test for BrowseForeigners::getHtmlForRelationalFieldSelection
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
        $result = BrowseForeigners::getHtmlForRelationalFieldSelection(
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
        $result = BrowseForeigners::getHtmlForRelationalFieldSelection(
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
