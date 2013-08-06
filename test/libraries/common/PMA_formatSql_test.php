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

class PMA_FormatSql_Test extends PHPUnit_Framework_TestCase
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

    function testFormatSQLfmTypeText_1()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';
        $unparsed = "SELECT 1;";
        $expected = '<span class="inner_sql">SELECT 1 ;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLfmTypeText_2()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT * from `tTable`;";
        $expected = '<span class="inner_sql">SELECT  * <br />FROM  `tTable` ;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLfmTypeText_3()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'text';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';
        $expected = '<span class="inner_sql">SELECT  * <br />FROM  `tTable_A` A<br />INNER  JOIN  `tTable_B` B ON B.ID = A.ID;<br /><br /></span>';

        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLfmTypeNone_1()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT 1;";

        $expected = "<span class=\"inner_sql\"><pre>\nSELECT 1;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLfmTypeNone_2()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = "SELECT * from `tTable`;";
        $expected = "<span class=\"inner_sql\"><pre>\nSELECT * from `tTable`;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLfmTypeNone_3()
    {
        global $cfg;
        $cfg['SQP']['fmtType'] = 'none';
        $cfg['MySQLManualType'] = 'none';

        $unparsed = 'SELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;';

        $expected = "<span class=\"inner_sql\"><pre>\nSELECT * FROM `tTable_A` A INNER JOIN `tTable_B` B ON B.ID = A.ID;\n</pre></span>";
        $this->assertEquals(
            $expected, PMA_Util::formatSql($unparsed)
        );
    }

    function testFormatSQLError()
    {
        global $SQP_errorString;
        $SQP_errorString = true;
        $this->assertEquals(
            "&amp; &quot; &lt; &gt;",
            PMA_Util::formatSql("& \" < >")
        );
        $SQP_errorString = false;
    }
}
