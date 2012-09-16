<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for quoting, slashing/backslashing
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/sqlparser.data.php';

class PMA_quoting_slashing_test extends PHPUnit_Framework_TestCase
{

    /**
     * sqlAddslashes test
     */
    public function testAddSlashes()
    {
        $string = "\'test''\''\'\r\t\n";
        
        $this->assertEquals("\\\\\\\\\'test\'\'\\\\\\\\\'\'\\\\\\\\\'\\r\\t\\n", PMA_Util::sqlAddSlashes($string, true, true, true));
        $this->assertEquals("\\\\\\\\''test''''\\\\\\\\''''\\\\\\\\''\\r\\t\\n", PMA_Util::sqlAddSlashes($string, true, true, false));
        $this->assertEquals("\\\\\\\\\'test\'\'\\\\\\\\\'\'\\\\\\\\\'\r\t\n", PMA_Util::sqlAddSlashes($string, true, false, true));
        $this->assertEquals("\\\\\\\\''test''''\\\\\\\\''''\\\\\\\\''\r\t\n", PMA_Util::sqlAddSlashes($string, true, false, false));
        $this->assertEquals("\\\\\'test\'\'\\\\\'\'\\\\\'\\r\\t\\n", PMA_Util::sqlAddSlashes($string, false, true, true));
        $this->assertEquals("\\\\''test''''\\\\''''\\\\''\\r\\t\\n", PMA_Util::sqlAddSlashes($string, false, true, false));
        $this->assertEquals("\\\\\'test\'\'\\\\\'\'\\\\\'\r\t\n", PMA_Util::sqlAddSlashes($string, false, false, true));
        $this->assertEquals("\\\\''test''''\\\\''''\\\\''\r\t\n", PMA_Util::sqlAddSlashes($string, false, false, false));
    }

    /**
     * data provider for PMA_Util::unQuote test
     *
     * @return array
     */
    public function unQuoteProvider()
    {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "test'"),
            array("`test'`", "test'"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * PMA_Util::unQuote test
     * @dataProvider unQuoteProvider
     */
    public function testUnQuote($param, $expected)
    {
        $this->assertEquals(
            $expected, PMA_Util::unQuote($param)
        );
    }

    /**
     * data provider for PMA_Util::unQuote test with chosen quote
     *
     * @return array
     */
    public function unQuoteSelectedProvider()
    {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "'test''"),
            array("`test'`", "`test'`"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * PMA_Util::unQuote test with chosen quote
     * @dataProvider unQuoteSelectedProvider
     */
    public function testUnQuoteSelectedChar($param, $expected)
    {
        $this->assertEquals(
            $expected, PMA_Util::unQuote($param, '"')
        );
    }

    /**
     * data provider for backquote test
     *
     * @return array
     */
    public function backquoteDataProvider()
    {
        return array(
            array('0', '`0`'),
            array('test', '`test`'),
            array('te`st', '`te``st`'),
            array(array('test', 'te`st', '', '*'), array('`test`', '`te``st`', '', '*'))
        );
    }

    /**
     * backquote test with different param $do_it (true, false)
     * @dataProvider backquoteDataProvider
     */
    public function testBackquote($a, $b)
    {
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, PMA_Util::backquote($a, false));

        // Test backquote
        $this->assertEquals($b, PMA_Util::backquote($a));
    }

    /**
     * data provider for backquoteCompat test
     *
     * @return array
     */
    public function backquoteCompatDataProvider()
    {
        return array(
            array('0', '"0"'),
            array('test', '"test"'),
            array('te`st', '"te`st"'),
            array(array('test', 'te`st', '', '*'), array('"test"', '"te`st"', '', '*'))
        );
    }

    /**
     * backquoteCompat test with different param $compatibility (NONE, MSSQL)
     * @dataProvider backquoteCompatDataProvider
     */
    public function testbackquoteCompat($a, $b)
    {
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, PMA_Util::backquoteCompat($a, 'NONE', false));

        // Test backquote (backquoting will be enabled only if isset $GLOBALS['sql_backquotes']
        $this->assertEquals($a, PMA_Util::backquoteCompat($a, 'NONE'));

        // Run tests in MSSQL compatibility mode
        // Test bypass quoting (used by dump functions)
        $this->assertEquals($a, PMA_Util::backquoteCompat($a, 'MSSQL', false));

        // Test backquote
        $this->assertEquals($b, PMA_Util::backquoteCompat($a, 'MSSQL'));
    }

    public function testBackquoteForbidenWords()
    {
        global $PMA_SQPdata_forbidden_word;

        foreach ($PMA_SQPdata_forbidden_word as $forbidden) {
            $this->assertEquals("`" . $forbidden . "`", PMA_Util::backquote($forbidden, false));
        }
    }
}
?>
