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
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.lib.php';

/**
 * PMA_SQLParserAnalyze_Test class
 *
 * this class is for testing sqlparser.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SQLParserAnalyze_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_SQP_getParserAnalyze
     *
     * @return void
     */
    public function testPMA_SQP_getParserAnalyzeMa()
    {
        //select statement
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

        $this->assertTrue($data['is_select']);
        $this->assertFalse($data['is_group']);
        $this->assertFalse($data['is_show']);

        //update statement
        $sql_query = "UPDATE `11`.`pma_bookmark` SET `id` = '2' WHERE `pma_bookmark`.`id` = 1;";
        $db = "PMA";
        $data = PMA_SQP_getParserAnalyzeMap($sql_query, $db);

        $this->assertEquals(
            array(
                'type' => 'alpha_reservedWord',
                'data' => 'UPDATE',
                'pos' => 6,
                'forbidden' => true,
            ),
            $data['parsed_sql'][0]
        );
        $this->assertEquals(
            $sql_query,
            $data['analyzed_sql'][0]['unsorted_query']
        );
        $this->assertFalse($data['is_group']);
        $this->assertFalse($data['is_show']);
        $this->assertTrue($data['is_affected']);
        $this->assertFalse($data['is_select']);
    }
}

?>
