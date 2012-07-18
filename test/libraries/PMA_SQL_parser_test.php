<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for correctness of SQL parser
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/sqlparser.lib.php';

class PMA_SQL_parser_test extends PHPUnit_Framework_TestCase
{
    private function assertParser($sql, $expected, $error = '')
    {
        PMA_SQP_resetError();
        $parsed_sql = PMA_SQP_parse($sql);
        $this->assertEquals($error, PMA_SQP_getErrorString());
        $this->assertEquals($expected, $parsed_sql);
    }

    public function testParse_1()
    {
        $this->assertParser(
            'SELECT 1;',
            array(
                'raw' => 'SELECT 1;',
                0 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'SELECT',
                    'pos' => 6,
                    'forbidden' => true,
                ),
                1 => array(
                    'type' => 'digit_integer',
                    'data' => '1',
                    'pos' => 8,
                ),
                2 => array(
                    'type' => 'punct_queryend',
                    'data' => ';',
                    'pos' => 0,
                ),
                'len' => 3,
            )
        );
    }

    public function testParse_2()
    {
        $this->assertParser(
            'SELECT * from aaa;',
            array(
                'raw' => 'SELECT * from aaa;',
                0 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'SELECT',
                    'pos' => 6,
                    'forbidden' => true,
                ),
                1 => array(
                    'type' => 'punct',
                    'data' => '*',
                    'pos' => 0,
                ),
                2 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'from',
                    'pos' => 13,
                    'forbidden' => true,
                ),
                3 => array(
                    'type' => 'alpha_identifier',
                    'data' => 'aaa',
                    'pos' => 17,
                    'forbidden' => false,
                ),
                4 => array(
                    'type' => 'punct_queryend',
                    'data' => ';',
                    'pos' => 0,
                ),
                'len' => 5,
            )
        );
    }

    public function testParse_3()
    {
        $this->assertParser(
            'SELECT * from `aaa`;',
            array(
                'raw' => 'SELECT * from `aaa`;',
                0 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'SELECT',
                    'pos' => 6,
                    'forbidden' => true,
                ),
                1 => array(
                    'type' => 'punct',
                    'data' => '*',
                    'pos' => 0,
                ),
                2 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'from',
                    'pos' => 13,
                    'forbidden' => true,
                ),
                3 => array(
                    'type' => 'quote_backtick',
                    'data' => '`aaa`',
                    'pos' => 0,
                ),
                4 => array(
                    'type' => 'punct_queryend',
                    'data' => ';',
                    'pos' => 0,
                ),
                'len' => 5,
            )
        );
    }

    /**
     *
     * @group medium
     */
    public function testParse_4()
    {
        $GLOBALS['is_ajax_request'] = true;
        $this->assertParser(
            'SELECT * from `aaa;',
            array(
                'raw' => 'SELECT * from `aaa`;',
                0 => array (
                    'type' => 'alpha_reservedWord',
                    'data' => 'SELECT',
                    'pos' => 6,
                    'forbidden' => true,
                ),
                1 => array(
                    'type' => 'punct',
                    'data' => '*',
                    'pos' => 0,
                ),
                2 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'from',
                    'pos' => 13,
                    'forbidden' => true,
                ),
                3 => array(
                    'type' => 'quote_backtick',
                    'data' => '`aaa`',
                    'pos' => 0,
                ),
                4 => array(
                    'type' => 'punct_queryend',
                    'data' => ';',
                    'pos' => 0,
                ),
                'len' => 5,
            )
        );
    }

    public function testParse_5()
    {
        $this->assertParser(
            'SELECT * FROM `a_table` tbla INNER JOIN b_table` tblb ON tblb.id = tbla.id WHERE tblb.field1 != tbla.field1`;',
            array(
                'raw' => 'SELECT * FROM `a_table` tbla INNER JOIN b_table` tblb ON tblb.id = tbla.id WHERE tblb.field1 != tbla.field1`;',
                0 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'SELECT',
                    'pos' => 6,
                    'forbidden' => true,
                ),
                1 => array(
                    'type' => 'punct',
                    'data' => '*',
                    'pos' => 0,
                ),
                2 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'FROM',
                    'pos' => 13,
                    'forbidden' => true,
                ),
                3 => array(
                    'type' => 'quote_backtick',
                    'data' => '`a_table`',
                    'pos' => 0,
                ),
                4 => array(
                    'type' => 'alpha_identifier',
                    'data' => 'tbla',
                    'pos' => 28,
                    'forbidden' => false,
                ),
                5 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'INNER',
                    'pos' => 34,
                    'forbidden' => true,
                ),
                6 => array(
                    'type' => 'alpha_reservedWord',
                    'data' => 'JOIN',
                    'pos' => 39,
                    'forbidden' => true,
                ),
                7 => array(
                    'type' => 'alpha_identifier',
                    'data' => 'b_table',
                    'pos' => 47,
                    'forbidden' => false,
                ),
                8 => array(
                    'type' => 'quote_backtick',
                    'data' => '` tblb ON tblb.id = tbla.id WHERE tblb.field1 != tbla.field1`',
                    'pos' => 0,
                ),
                9 => array(
                    'type' => 'punct_queryend',
                    'data' => ';',
                    'pos' => 0,
                ),
                'len' => 10,
            )
        );
    }
}
?>
