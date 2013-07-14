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

class PMA_SQLParser_Data_Test extends PHPUnit_Framework_TestCase
{
    private function _assertSorted($array)
    {
        $copy = $array;
        sort($copy);
        $difference = array_diff_assoc($array, $copy);
        $this->assertEquals($difference, array());
    }

    private function _assertParserData($name)
    {
        $this->_assertSorted($GLOBALS[$name]);
    }

    public function testPMA_SQPdata()
    {
        $data = PMA_SQL_Parse_Data_Mock::getDataArray();
        $this->_assertSorted($data['PMA_SQPdata_function_name']);
        $this->_assertSorted($data['PMA_SQPdata_column_attrib']);
        $this->_assertSorted($data['PMA_SQPdata_reserved_word']);
        $this->_assertSorted($data['PMA_SQPdata_forbidden_word']);
        $this->_assertSorted($data['PMA_SQPdata_column_type']);
    }

    public function testPMA_SQPdata_function_name()
    {
        $this->_assertParserData('PMA_SQPdata_function_name');
    }

    public function testPMA_SQPdata_column_attrib()
    {
        $this->_assertParserData('PMA_SQPdata_column_attrib');
    }

    public function testPMA_SQPdata_reserved_word()
    {
        $this->_assertParserData('PMA_SQPdata_reserved_word');
    }

    public function testPMA_SQPdata_forbidden_word()
    {
        $this->_assertParserData('PMA_SQPdata_forbidden_word');
    }

    public function testPMA_SQPdata_column_type()
    {
        $this->_assertParserData('PMA_SQPdata_column_type');
    }

}

class PMA_SQL_Parse_Data_Mock 
{
    public static function getDataArray(){
        //so that sqlparser.data.php can be covered by PHPUnit
        include 'libraries/sqlparser.data.php';
        return array(
            'PMA_SQPdata_function_name'  => $PMA_SQPdata_function_name,
            'PMA_SQPdata_column_attrib'  => $PMA_SQPdata_column_attrib,
            'PMA_SQPdata_reserved_word'  => $PMA_SQPdata_reserved_word,
            'PMA_SQPdata_forbidden_word' => $PMA_SQPdata_forbidden_word,
            'PMA_SQPdata_column_type'    => $PMA_SQPdata_column_type,
        );
    }
}

?>
