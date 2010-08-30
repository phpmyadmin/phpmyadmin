<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for format number and byte
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_formatNumberByteDown_test.php
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
 * Test formating number and byte.
 *
 */
class PMA_formatNumberByteDown_test extends PHPUnit_Framework_TestCase
{

    /**
     * temporary variable for globals array
     */

    protected $tmpGlobals;

    /**
     * temporary variable for session array
     */

    protected $tmpSession;

    /**
     * storing globals and session
     */
    public function setUp() {

        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;

    }

    /**
     * recovering globals and session
     */
    public function tearDown() {

        $GLOBALS = $this->tmpGlobals;
        $_SESSION = $this->tmpSession;

    }

    /**
     * format number data provider
     */

    public function formatNumberDataProvider() {
        return array(
            array(10, 2, 2, '10,00  '),
            array(100, 2, 0, '100  '),
            array(100, 2, 2, '0,10 k'),
            array(-1000.454, 4, 2, '-1 000,45  '),
            array(0.00003, 3, 2, '0,03 m'),
            array(0.003, 3, 3, '0,003  '),
            array(-0.003, 6, 0, '-3 m'),
            array(100.98, 0, 2, '100,98')
        );
    }

    /**
     * format number test, globals are defined
     * @dataProvider formatNumberDataProvider
     */

    public function testFormatNumber($a, $b, $c, $e) {
        $this->assertEquals($e, (string)PMA_formatNumber($a, $b, $c, false));
    }

    /**
     * format byte down data provider
     */

    public function formatByteDownDataProvider() {
        return array(
            array(10, 2, 2, array('10', 'B')),
            array(100, 2, 0, array('0', 'KB')),
            array(100, 3, 0, array('100', 'B')),
            array(100, 2, 2, array('0,10', 'KB')),
            array(1034, 3, 2, array('1,01', 'KB')),
            array(100233, 3, 3, array('97,884', 'KB')),
            array(2206451, 1, 2, array('2,10', 'MB'))
        );
    }

    /**
     * format byte test, globals are defined
     * @dataProvider formatByteDownDataProvider
     */

    public function testFormatByteDown($a, $b, $c, $e) {
        $result = PMA_formatByteDown($a, $b, $c);
        $result[0] = trim($result[0]);
        $this->assertEquals($e, $result);
    }
}
?>
