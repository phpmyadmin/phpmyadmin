<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test printableBitValue function
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_printableBitValue_test.php
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
 * Test printableBitValue function.
 *
 */
class PMA_printableBitValue_test extends PHPUnit_Framework_TestCase
{

    /**
     * data provider for printable bit value test
     */
    
    public function printableBitValueDataProvider() {
        return array(
            array('testtest', 64, '0111010001100101011100110111010001110100011001010111001101110100'),
            array('test', 32, '01110100011001010111001101110100')
        );
    }

    /**
     * test for generating string contains printable bit value of selected data
     * @dataProvider printableBitValueDataProvider
     */

    public function testPrintableBitValue($a, $b, $e) {
        $this->assertEquals($e, PMA_printable_bit_value($a, $b));
    }
}
?>
