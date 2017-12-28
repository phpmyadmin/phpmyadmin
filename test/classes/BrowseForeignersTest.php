<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\BrowseForeigners
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\BrowseForeigners;
use PHPUnit\Framework\TestCase;

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
    public function setUp()
    {
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['MaxRows'] = 25;
        $GLOBALS['cfg']['RepeatCells'] = 100;
        $GLOBALS['cfg']['ShowAll'] = false;
        $GLOBALS['pmaThemeImage'] = '';
    }

    /**
     * Test for BrowseForeigners::getForeignLimit
     *
     * @return void
     */
    function testGetForeignLimit()
    {
        $this->assertNull(
            BrowseForeigners::getForeignLimit(
                $GLOBALS['cfg']['MaxRows'],
                'Show all'
            )
        );

        $this->assertEquals(
            'LIMIT 0, 25 ',
            BrowseForeigners::getForeignLimit(
                $GLOBALS['cfg']['MaxRows'],
                null
            )
        );

        $_REQUEST['pos'] = 10;

        $this->assertEquals(
            'LIMIT 10, 25 ',
            BrowseForeigners::getForeignLimit(
                $GLOBALS['cfg']['MaxRows'],
                null
            )
        );

        $GLOBALS['cfg']['MaxRows'] = 50;

        $this->assertEquals(
            'LIMIT 10, 50 ',
            BrowseForeigners::getForeignLimit(
                $GLOBALS['cfg']['MaxRows'],
                null
            )
        );

        $this->assertEquals(
            'LIMIT 10, 50 ',
            BrowseForeigners::getForeignLimit(
                $GLOBALS['cfg']['MaxRows'],
                'xyz'
            )
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
            BrowseForeigners::getHtmlForShowAll(
                $GLOBALS['cfg']['ShowAll'],
                $GLOBALS['cfg']['MaxRows'],
                null
            )
        );

        $foreignData = array();
        $foreignData['disp_row'] = array();
        $GLOBALS['cfg']['ShowAll'] = false;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForShowAll(
                $GLOBALS['cfg']['ShowAll'],
                $GLOBALS['cfg']['MaxRows'],
                $foreignData
            )
        );

        $GLOBALS['cfg']['ShowAll'] = true;
        $foreignData['the_total'] = 0;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForShowAll(
                $GLOBALS['cfg']['ShowAll'],
                $GLOBALS['cfg']['MaxRows'],
                $foreignData
            )
        );

        $foreignData['the_total'] = 30;

        $this->assertContains(
            '<input type="submit" id="foreign_showAll" '
            . 'name="foreign_showAll" '
            . 'value="' . 'Show all' . '">',
            BrowseForeigners::getHtmlForShowAll(
                $GLOBALS['cfg']['ShowAll'],
                $GLOBALS['cfg']['MaxRows'],
                $foreignData
            )
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
            BrowseForeigners::getHtmlForGotoPage(
                $GLOBALS['cfg']['MaxRows'],
                null
            )
        );

        $_REQUEST['pos'] = 15;
        $foreignData = array();
        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;

        $this->assertEquals(
            '',
            BrowseForeigners::getHtmlForGotoPage(
                $GLOBALS['cfg']['MaxRows'],
                $foreignData
            )
        );

        $foreignData['the_total'] = 30;
        $result = BrowseForeigners::getHtmlForGotoPage(
            $GLOBALS['cfg']['MaxRows'],
            $foreignData
        );

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
            BrowseForeigners::getDescriptionAndTitle(
                $GLOBALS['cfg']['LimitChars'],
                $desc
            )
        );

        $GLOBALS['cfg']['LimitChars'] = 5;

        $this->assertEquals(
            array('fooba...', 'foobar&lt;baz'),
            BrowseForeigners::getDescriptionAndTitle(
                $GLOBALS['cfg']['LimitChars'],
                $desc
            )
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
            $GLOBALS['cfg']['RepeatCells'],
            $GLOBALS['pmaThemeImage'],
            $GLOBALS['cfg']['MaxRows'],
            $GLOBALS['cfg']['ShowAll'],
            $GLOBALS['cfg']['LimitChars'],
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $current_value
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
            $GLOBALS['cfg']['RepeatCells'],
            $GLOBALS['pmaThemeImage'],
            $GLOBALS['cfg']['MaxRows'],
            $GLOBALS['cfg']['ShowAll'],
            $GLOBALS['cfg']['LimitChars'],
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $current_value
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
