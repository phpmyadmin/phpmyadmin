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
require_once 'libraries/sqlparser.data.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Message.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/sanitizing.lib.php';

/**
 * PMA_SQLParser_Test class
 *
 * this class is for testing sqlparser.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SQLParser_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    public function setUp()
    {
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('utf-8');
        }
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'theme/';
    }

    /**
     * Testing of SQL parser.
     *
     * @param string $sql      SQL query to parse
     * @param array  $expected Expected parse result
     * @param string $error    Expected error message
     *
     * @return void
     *
     * @dataProvider parserData
     * @group medium
     */
    public function testParser($sql, $expected, $error = '')
    {
        PMA_SQP_resetError();
        $this->expectOutputString($error);
        $parsed_sql = PMA_SQP_parse($sql);
        $this->assertEquals('', PMA_SQP_getErrorString());
        $this->assertEquals($expected, $parsed_sql);
    }

    /**
     * Test for PMA_SQP_isKeyWord
     *
     * @return void
     */
    public function testPMA_SQP_isKeyWord()
    {
        PMA_SQP_resetError();
        $this->assertTrue(PMA_SQP_isKeyWord("ACCESSIBLE"));
        $this->assertTrue(PMA_SQP_isKeyWord("accessible"));
        $this->assertTrue(PMA_SQP_isKeyWord("ASC"));
        $this->assertFalse(PMA_SQP_isKeyWord("hello"));
    }

    /**
     * Test PMA_SQP_typeCheck
     *
     * @return void
     */
    public function testPMA_SQP_typeCheck()
    {
        $this->assertTrue(
            PMA_SQP_typeCheck("VARCHAR", "VARCHAR")
        );

        $this->assertFalse(
            PMA_SQP_typeCheck("VARCHAR", "VARCHAR_INT")
        );

        $this->assertTrue(
            PMA_SQP_typeCheck("VARCHAR_INT", "VARCHAR")
        );

        $this->assertFalse(
            PMA_SQP_typeCheck("TIME_INT", "VARCHAR")
        );
    }

    /**
     * Test PMA_SQP_throwError
     *
     * @return void
     */
    public function testPMA_SQP_throwError()
    {
        global $SQP_errorString;
        $message = "error from testPMA_SQP_throwError";
        $sql = "select * from PMA.PMABookmark";
        PMA_SQP_throwError($message, $sql);

        $this->assertContains(
            "There seems to be an error in your SQL query.",
            $SQP_errorString
        );

        $this->assertContains(
            'ERROR: ' . $message,
            $SQP_errorString
        );

        $this->assertContains(
            'SQL: ' . htmlspecialchars($sql),
            $SQP_errorString
        );
    }

    /**
     * Data provider for parser testing
     *
     * @return array with test data
     */
    public function parserData()
    {
        return array(
            array(
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
            ),
            array(
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
            ),
            array(
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
            ),
            array(
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
                ),
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" /> Automatically appended backtick to the end of query!</div>'
            ),
            array(
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
            ),
        );
    }
}
?>
