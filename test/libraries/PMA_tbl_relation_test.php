<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/tbl_relation.lib.php';

/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblRelationTest extends PHPUnit_Framework_TestCase
{
    /**
     * Tests for PMA_generateRelationalDropdown() method.
     *
     * @return void
     * @test
     */
    public function testGenerateRelationalDropdown()
    {
        // test for start tag
        $this->assertStringStartsWith(
            '<select',
            PMA_generateRelationalDropdown('name')
        );

        // test for end tag
        $this->assertStringEndsWith(
            '</select>',
            PMA_generateRelationalDropdown('name')
        );

        // test for name
        $this->assertStringStartsWith(
            '<select name="name"',
            PMA_generateRelationalDropdown('name')
        );

        // test for title
        $this->assertStringStartsWith(
            '<select name="name" title="title"',
            PMA_generateRelationalDropdown('name', array(), false, 'title')
        );

        $values = array('value1', '<alue2', 'value3');
        // test for empty option
        $this->assertContains(
            '<option value=""></option>',
            PMA_generateRelationalDropdown('name', $values)
        );

        // test for options and escaping
        $this->assertContains(
            '<option value="&lt;alue2">&lt;alue2</option>',
            PMA_generateRelationalDropdown('name', $values)
        );

        // test for selected option
        $this->assertContains(
            '<option value="value1" selected="selected">value1</option>',
            PMA_generateRelationalDropdown('name', $values, 'value1')
        );

        // test for selected value not found in values array and its escaping
        $this->assertContains(
            '<option value="valu&lt;4" selected="selected">valu&lt;4</option></select>',
            PMA_generateRelationalDropdown('name', $values, 'valu<4')
        );
    }
}
?>