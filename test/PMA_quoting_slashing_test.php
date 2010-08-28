<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for quoting, slashing/backslashing
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_quoting_slashing_test.php
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/common.lib.php';

/**
 * Test quoting, slashing, backslashing.
 *
 */
class PMA_quoting_slashing_test extends PHPUnit_Framework_TestCase
{

    /**
     * sqlAddslashes test
     */

    public function testAddSlashes() {
        $string = "\'test''\''\'\r\t\n";

        $this->assertEquals("\\\\\\\\\'test\'\'\\\\\\\\\'\'\\\\\\\\\'\\r\\t\\n", PMA_sqlAddslashes($string, true, true, true));
        $this->assertEquals("\\\\\\\\''test''''\\\\\\\\''''\\\\\\\\''\\r\\t\\n", PMA_sqlAddslashes($string, true, true, false));
        $this->assertEquals("\\\\\\\\\'test\'\'\\\\\\\\\'\'\\\\\\\\\'\r\t\n", PMA_sqlAddslashes($string, true, false, true));
        $this->assertEquals("\\\\\\\\''test''''\\\\\\\\''''\\\\\\\\''\r\t\n", PMA_sqlAddslashes($string, true, false, false));
        $this->assertEquals("\\\\\'test\'\'\\\\\'\'\\\\\'\\r\\t\\n", PMA_sqlAddslashes($string, false, true, true));
        $this->assertEquals("\\\\''test''''\\\\''''\\\\''\\r\\t\\n", PMA_sqlAddslashes($string, false, true, false));
        $this->assertEquals("\\\\\'test\'\'\\\\\'\'\\\\\'\r\t\n", PMA_sqlAddslashes($string, false, false, true));
        $this->assertEquals("\\\\''test''''\\\\''''\\\\''\r\t\n", PMA_sqlAddslashes($string, false, false, false));
    }

    /**
     * data provider for unQuote test
     */

    public function unQuoteProvider() {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "test'"),
            array("`test'`", "test'"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * unQuote test
     * @dataProvider unQuoteProvider
     */

    public function testUnQuote($param, $expected) {
        $this->assertEquals($expected, PMA_unQuote($param));
    }

    /**
     * data provider for unQuote test with chosen quote
     */

    public function unQuoteSelectedProvider() {
        return array(
            array('"test\'"', "test'"),
            array("'test''", "'test''"),
            array("`test'`", "`test'`"),
            array("'test'test", "'test'test")
        );
    }

    /**
     * unQuote test with chosen quote
     * @dataProvider unQuoteSelectedProvider
     */

    public function testUnQuoteSelectedChar($param, $expected) {
        $this->assertEquals($expected, PMA_unQuote($param, '"'));
    }

    /**
     * data provider for backquote test
     */

    public function backquoteDataProvider() {
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

    public function testBackquote($a, $b) {
        $this->assertEquals($a, PMA_backquote($a, false));
        $this->assertEquals($b, PMA_backquote($a));
    }
}
?>
