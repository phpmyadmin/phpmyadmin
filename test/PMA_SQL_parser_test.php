<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for correctness of SQL parser
 *
 * @package phpMyAdmin-test
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

define('PHPMYADMIN', 1);
define('TESTSUITE', 1);
$GLOBALS['charset'] = 'utf-8';

/**
 * Include to test.
 */
require_once './libraries/sqlparser.lib.php';

/**
 * Test for SQL parser
 *
 * @package phpMyAdmin-test
 */
class PMA_SQL_parser_test extends PHPUnit_Framework_TestCase
{
    private function assertParser($sql, $expected)
    {
        $parsed_sql = PMA_SQP_parse($sql);
        $this->assertEquals($parsed_sql, $expected);
    }

    public function testParse_1()
    {
        $this->assertParser('SELECT 1;', array (
          'raw' => 'SELECT 1;',
          0 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'SELECT',
            'pos' => 6,
            'forbidden' => true,
          ),
          1 =>
          array (
            'type' => 'digit_integer',
            'data' => '1',
            'pos' => 8,
          ),
          2 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 3,
        ));
    }

}
?>
