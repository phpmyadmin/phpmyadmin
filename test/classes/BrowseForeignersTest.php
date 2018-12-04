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
use ReflectionClass;

/**
 * Tests for PhpMyAdmin\BrowseForeigners
 *
 * @package PhpMyAdmin-test
 */
class BrowseForeignersTest extends TestCase
{
    private $browseForeigners;

    /**
     * Setup for test cases
     *
     * @return void
     */
    protected function setUp()
    {
        $this->browseForeigners = new BrowseForeigners(50, 25, 100, false, '');
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string           $name   method name
     * @param array            $params parameters for the invocation
     * @param BrowseForeigners $object BrowseForeigners instance object
     *
     * @return mixed the output from the protected method.
     */
    private function callProtectedMethod($name, $params, BrowseForeigners $object = null)
    {
        $class = new ReflectionClass(BrowseForeigners::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(
            $object !== null ? $object : $this->browseForeigners,
            $params
        );
    }

    /**
     * Test for BrowseForeigners::getForeignLimit
     *
     * @return void
     */
    function testGetForeignLimit()
    {
        $this->assertNull(
            $this->browseForeigners->getForeignLimit('Show all')
        );

        $this->assertEquals(
            'LIMIT 0, 25 ',
            $this->browseForeigners->getForeignLimit(null)
        );

        $_POST['pos'] = 10;

        $this->assertEquals(
            'LIMIT 10, 25 ',
            $this->browseForeigners->getForeignLimit(null)
        );

        $browseForeigners = new BrowseForeigners(
            50,
            50,
            100,
            false,
            ''
        );

        $this->assertEquals(
            'LIMIT 10, 50 ',
            $browseForeigners->getForeignLimit(null)
        );

        $this->assertEquals(
            'LIMIT 10, 50 ',
            $browseForeigners->getForeignLimit('xyz')
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
            $this->callProtectedMethod(
                'getHtmlForGotoPage',
                [null]
            )
        );

        $_POST['pos'] = 15;
        $foreignData = array();
        $foreignData['disp_row'] = array();
        $foreignData['the_total'] = 5;

        $this->assertEquals(
            '',
            $this->callProtectedMethod(
                'getHtmlForGotoPage',
                [$foreignData]
            )
        );

        $foreignData['the_total'] = 30;
        $result = $this->callProtectedMethod(
            'getHtmlForGotoPage',
            [$foreignData]
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
        $desc = 'foobar<baz';

        $this->assertEquals(
            array('foobar&lt;baz', ''),
            $this->callProtectedMethod(
                'getDescriptionAndTitle',
                [$desc]
            )
        );

        $browseForeigners = new BrowseForeigners(5, 25, 100, false, '');

        $this->assertEquals(
            array('fooba...', 'foobar&lt;baz'),
            $this->callProtectedMethod(
                'getDescriptionAndTitle',
                [$desc],
                $browseForeigners
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
        $_POST['rownumber'] = 1;
        $_POST['foreign_filter'] = '5';
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
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
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
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
