<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::formatSql from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.lib.php';

class PMA_formatSql_test extends PHPUnit_Framework_TestCase
{

    /**
     * temporary variable for globals array
     */
    protected $tmpCfg;

    /**
     * temporary variable for session array
     */
    protected $tmpSession;

    /**
     * storing globals and session
     */
    public function setUp()
    {
        global $cfg;
        $this->tmpCfg = $cfg;
    }

    /**
     * recovering globals and session
     */
    public function tearDown()
    {
        global $cfg;
        $cfg = $this->tmpCfg;
    }

    function testFormatSQLNotArray()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'html';
        $sql = "SELECT * FROM tTable;";
        $this->assertEquals(
            "<pre>\n$sql\n</pre>",
            PMA_Util::formatSql($sql)
        );
    }

    function testFormatSQLfmTypeHtml_1()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'html';
        $cfg['MySQLManualType'] = 'none';

        $sql = array (
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
        );
        $unparsed = "SELECT 1;";
        $expected = '<span class="syntax"><span class="inner_sql"><span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span></a> <span class="syntax_digit syntax_digit_integer">1</span> <span class="syntax_punct syntax_punct_queryend">;</span><br /><br /></span></span>';

        $this->assertEquals(
            $expected,
            PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeHtml_2()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'html';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT * from `tTable`;";
        $sql = array (
          'raw' => $unparsed,
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
            'data' => '`tTable`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 5,
        );
        $expected = '<span class="syntax"><span class="inner_sql"><span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span></a>  <span class="syntax_punct">*</span> <br /><span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>  <span class="syntax_quote syntax_quote_backtick">`tTable`</span> <span class="syntax_punct syntax_punct_queryend">;</span><br /><br /></span></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeHtml_3()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'html';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';
        $sql = array (
          'raw' => $unparsed,
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
            'data' => 'FROM',
            'pos' => 13,
            'forbidden' => true,
          ),
          3 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_A`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 26,
            'forbidden' => false,
          ),
          5 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'INNER',
            'pos' => 32,
            'forbidden' => true,
          ),
          6 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'JOIN',
            'pos' => 37,
            'forbidden' => true,
          ),
          7 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_B`',
            'pos' => 0,
          ),
          8 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 50,
            'forbidden' => false,
          ),
          9 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'ON',
            'pos' => 53,
            'forbidden' => true,
          ),
          10 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 55,
            'forbidden' => false,
          ),
          11 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          12 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 58,
            'forbidden' => false,
          ),
          13 =>
          array (
            'type' => 'punct',
            'data' => '=',
            'pos' => 0,
          ),
          14 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 62,
            'forbidden' => false,
          ),
          15 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          16 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 65,
            'forbidden' => false,
          ),
          17 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 18,
        );

        $expected = '<span class="syntax"><span class="inner_sql"><span class="syntax_alpha syntax_alpha_reservedWord">SELECT</span></a>  <span class="syntax_punct">*</span> <br /><span class="syntax_alpha syntax_alpha_reservedWord">FROM</span>  <span class="syntax_quote syntax_quote_backtick">`tTable_A`</span> <span class="syntax_alpha syntax_alpha_identifier">A</span><br /><span class="syntax_alpha syntax_alpha_reservedWord">INNER</span>  <span class="syntax_alpha syntax_alpha_reservedWord">JOIN</span>  <span class="syntax_quote syntax_quote_backtick">`tTable_B`</span> <span class="syntax_alpha syntax_alpha_identifier">B</span> <span class="syntax_alpha syntax_alpha_reservedWord">ON</span> <span class="syntax_alpha syntax_alpha_identifier">B</span><span class="syntax_punct syntax_punct_qualifier">.</span><span class="syntax_alpha syntax_alpha_identifier">ID</span> <span class="syntax_punct">=</span></a> <span class="syntax_alpha syntax_alpha_identifier">A</span><span class="syntax_punct syntax_punct_qualifier">.</span><span class="syntax_alpha syntax_alpha_identifier">ID</span><span class="syntax_punct syntax_punct_queryend">;</span><br /><br /></span></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeText_1()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';

        $sql = array (
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
        );
        $unparsed = "SELECT 1;";
        $expected = '<span class="inner_sql">SELECT</a> 1 ;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeText_2()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT * from `tTable`;";
        $sql = array (
          'raw' => $unparsed,
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
            'data' => '`tTable`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 5,
        );
        $expected = '<span class="inner_sql">SELECT</a>  * <br />FROM  `tTable` ;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeText_3()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';
        $sql = array (
          'raw' => $unparsed,
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
            'data' => 'FROM',
            'pos' => 13,
            'forbidden' => true,
          ),
          3 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_A`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 26,
            'forbidden' => false,
          ),
          5 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'INNER',
            'pos' => 32,
            'forbidden' => true,
          ),
          6 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'JOIN',
            'pos' => 37,
            'forbidden' => true,
          ),
          7 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_B`',
            'pos' => 0,
          ),
          8 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 50,
            'forbidden' => false,
          ),
          9 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'ON',
            'pos' => 53,
            'forbidden' => true,
          ),
          10 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 55,
            'forbidden' => false,
          ),
          11 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          12 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 58,
            'forbidden' => false,
          ),
          13 =>
          array (
            'type' => 'punct',
            'data' => '=',
            'pos' => 0,
          ),
          14 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 62,
            'forbidden' => false,
          ),
          15 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          16 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 65,
            'forbidden' => false,
          ),
          17 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 18,
        );
        $expected = '<span class="inner_sql">SELECT</a>  * <br />FROM  `tTable_A` A<br />INNER  JOIN  `tTable_B` B ON B.ID =</a> A.ID;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );
    }

    function testFormatSQLfmTypeNone_1()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $sql = array (
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
        );
        $unparsed = "SELECT 1;";

        $expected = "<span class=\"inner_sql\"><pre>\nSELECT 1;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );

        $expected = "SELECT 1;";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql)
        );
    }

    function testFormatSQLfmTypeNone_2()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT * from `tTable`;";
        $sql = array (
          'raw' => $unparsed,
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
            'data' => '`tTable`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 5,
        );

        $expected = "<span class=\"inner_sql\"><pre>\nSELECT * from `tTable`;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );

        $expected = "SELECT * from `tTable`;";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql)
        );
    }

    function testFormatSQLfmTypeNone_3()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';
        $sql = array (
          'raw' => $unparsed,
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
            'data' => 'FROM',
            'pos' => 13,
            'forbidden' => true,
          ),
          3 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_A`',
            'pos' => 0,
          ),
          4 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 26,
            'forbidden' => false,
          ),
          5 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'INNER',
            'pos' => 32,
            'forbidden' => true,
          ),
          6 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'JOIN',
            'pos' => 37,
            'forbidden' => true,
          ),
          7 =>
          array (
            'type' => 'quote_backtick',
            'data' => '`tTable_B`',
            'pos' => 0,
          ),
          8 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 50,
            'forbidden' => false,
          ),
          9 =>
          array (
            'type' => 'alpha_reservedWord',
            'data' => 'ON',
            'pos' => 53,
            'forbidden' => true,
          ),
          10 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'B',
            'pos' => 55,
            'forbidden' => false,
          ),
          11 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          12 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 58,
            'forbidden' => false,
          ),
          13 =>
          array (
            'type' => 'punct',
            'data' => '=',
            'pos' => 0,
          ),
          14 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'A',
            'pos' => 62,
            'forbidden' => false,
          ),
          15 =>
          array (
            'type' => 'punct_qualifier',
            'data' => '.',
            'pos' => 0,
          ),
          16 =>
          array (
            'type' => 'alpha_identifier',
            'data' => 'ID',
            'pos' => 65,
            'forbidden' => false,
          ),
          17 =>
          array (
            'type' => 'punct_queryend',
            'data' => ';',
            'pos' => 0,
          ),
          'len' => 18,
        );

        $expected = "<span class=\"inner_sql\"><pre>\nSELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql, $unparsed)
        );

        $expected = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';
        $this->assertEquals(
            $expected, PMA_Util::formatSql($sql)
        );
    }

    function testFormatSQLWithoutType()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = '';
        $cfg['MySQLManualType'] = 'none';
        $sql = array (
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
        );
        $this->assertEmpty(PMA_Util::formatSql($sql));
    }

    function testFormatSQLError()
    {
        global $SQP_errorString;
        $SQP_errorString = true;
        $sql = array("raw" => "& \" < >");
        $this->assertEquals(
            "&amp; &quot; &lt; &gt;",
            PMA_Util::formatSql($sql)
        );
        $SQP_errorString = false;
    }
}
