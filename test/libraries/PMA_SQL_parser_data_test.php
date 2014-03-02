<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for correctness of SQL parser data
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sqlparser.data.php';
require_once 'libraries/sqlparser.lib.php';

/**
 * PMA_SQLParser_Data_Test class
 *
 * this class is for testing sqlparser.data.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SQLParser_Data_Test extends PHPUnit_Framework_TestCase
{
    /**
     * assert Sorted values
     *
     * @param array $array Sorted values array
     *
     * @return null
     */
    private function _assertSorted($array)
    {
        $copy = $array;
        sort($copy);
        $difference = array_diff_assoc($array, $copy);
        $this->assertEquals($difference, array());
    }

    /**
     * assert Parser values
     *
     * @param string $name Parser Data key
     *
     * @return null
     */
    private function _assertParserData($name)
    {
        $this->_assertSorted($GLOBALS[$name]);
    }


    /**
     * Test for PMA_SQPdata
     *
     * @return void
     */
    public function testPMA_SQPdata()
    {
        $data = PMA_SQP_getParserDataMap();
        $this->_assertSorted($data['PMA_SQPdata_function_name']);
        $this->_assertSorted($data['PMA_SQPdata_column_attrib']);
        $this->_assertSorted($data['PMA_SQPdata_reserved_word']);
        $this->_assertSorted($data['PMA_SQPdata_forbidden_word']);
        $this->_assertSorted($data['PMA_SQPdata_column_type']);
    }


    /**
     * Test for PMA_SQPdata_function_name
     *
     * @return void
     */
    public function testPMA_SQPdata_function_name()
    {
        $this->_assertParserData('PMA_SQPdata_function_name');
    }


    /**
     * Test for PMA_SQPdata_column_attrib
     *
     * @return void
     */
    public function testPMA_SQPdata_column_attrib()
    {
        $this->_assertParserData('PMA_SQPdata_column_attrib');
    }


    /**
     * Test for PMA_SQPdata_reserved_word
     *
     * @return void
     */
    public function testPMA_SQPdata_reserved_word()
    {
        $this->_assertParserData('PMA_SQPdata_reserved_word');
    }


    /**
     * Test for PMA_SQPdata_forbidden_word
     *
     * @return void
     */
    public function testPMA_SQPdata_forbidden_word()
    {
        $this->_assertParserData('PMA_SQPdata_forbidden_word');
    }


    /**
     * Test for PMA_SQPdata_column_type
     *
     * @return void
     */
    public function testPMA_SQPdata_column_type()
    {
        $this->_assertParserData('PMA_SQPdata_column_type');
    }

}

?>
