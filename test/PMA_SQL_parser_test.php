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

function __($s) {
    return $s;
}

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
    private function assertParser($sql, $expected, $error = '')
    {
        $parsed_sql = PMA_SQP_parse($sql);
        $this->assertEquals(PMA_SQP_getErrorString(), $error);
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

    public function testParse_2()
    {
        $this->assertParser('SELECT * from aaa;', array (
          'raw' => 'SELECT * from aaa;',
          0 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'SELECT',
            'pos' => 6,
            'forbidden' => true,
          ),
          1 =>
          array (
            'type' => 'punct',
            'data' => '*',
            'pos' => 0,
          ),
          2 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'from',
            'pos' => 13,
            'forbidden' => true,
          ),
          3 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'aaa',
            'pos' => 17,
            'forbidden' => false,
          ),
          4 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 5,
        ));
    }

    public function testParse_3()
    {
        $this->assertParser('SELECT * from `aaa`;', array (
          'raw' => 'SELECT * from `aaa`;',
          0 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'SELECT',
            'pos' => 6,
            'forbidden' => true,
          ),
          1 =>
          array (
            'type' => 'punct',
            'data' => '*',
            'pos' => 0,
          ),
          2 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'from',
            'pos' => 13,
            'forbidden' => true,
          ),
          3 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`aaa`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 5,
        ));
    }

    public function testParse_4()
    {
        $this->assertParser('SELECT * from `aaa;', array (
          'raw' => 'SELECT * from `aaa;',
          0 =>
          array (
            'type' => 'alpha',
            'data' => 'SELECT',
            'pos' => 6,
          ),
          1 =>
          array (
            'type' => 'punct',
            'data' => '*',
            'pos' => 0,
          ),
          2 =>
          array (
            'type' => 'alpha',
            'data' => 'from',
            'pos' => 13,
          ),
        ),
'<p>There seems to be an error in your SQL query. The MySQL server error output below, if there is any, may also help you in diagnosing the problem</p>
<pre>
ERROR: Unclosed quote @ 14
STR: `
SQL: SELECT * from `aaa;
</pre>
');
    }
}
?>
