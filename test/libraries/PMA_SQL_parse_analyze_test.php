<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for correctness of SQL parser analyze data
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sqlparser.lib.php';

class PMA_SQLParserAnalyze_Test extends PHPUnit_Framework_TestCase
{
    public function testPMA_SQP_getParserAnalyzeMa()
    {
        $sql_query = "select * from PMA.PMAParse";
        $db = "PMA";
        $data = PMA_SQP_getParserAnalyzeMap($sql_query, $db);
        
        $this->assertEquals(
            array(
                'type' => 'alpha_reservedWord',
                'data' => 'select',
                'pos' => 6,
                'forbidden' => true,
            ),
            $data['parsed_sql'][0]
        );
        $this->assertEquals(
            'select * from PMA.PMAParse',
            $data['analyzed_sql'][0]['unsorted_query']
        );
        $this->assertFalse($data['reload']);
        $this->assertFalse($data['is_group']);
        $this->assertFalse($data['is_show']);
        $this->assertFalse($data['is_group']);
    }
}

?>
