<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for correctness of SQL parser data
 *
 * @package phpMyAdmin-test
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

define('PHPMYADMIN', 1);

/**
 * Include to test.
 */
require_once './libraries/sqlparser.data.php';

/**
 * Test for sorting of the arrays
 *
 * @package phpMyAdmin-test
 */
class PMA_SQL_parser_data_test extends PHPUnit_Framework_TestCase
{
    private function assertSorted($array)
    {
        $copy = $array;
        sort($copy);
        $difference = array_diff_assoc($array, $copy);
        $this->assertEquals($difference, array());
    }

    private function assertParserData($name)
    {
        $this->assertSorted($GLOBALS[$name]);
        $this->assertEquals(count($GLOBALS[$name]), $GLOBALS[$name . '_cnt']);
    }

    public function testPMA_SQPdata_function_name()
    {
        $this->assertParserData('PMA_SQPdata_function_name');
    }

    public function testPMA_SQPdata_column_attrib()
    {
        $this->assertParserData('PMA_SQPdata_column_attrib');
    }

    public function testPMA_SQPdata_reserved_word()
    {
        $this->assertParserData('PMA_SQPdata_reserved_word');
    }

    public function testPMA_SQPdata_forbidden_word()
    {
        $this->assertParserData('PMA_SQPdata_forbidden_word');
    }

    public function testPMA_SQPdata_column_type()
    {
        $this->assertParserData('PMA_SQPdata_column_type');
    }

}
?>
