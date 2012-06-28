<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test PMA_CommonFunctions::printableBitValue function
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';

class PMA_PrintableBitValueTest extends PHPUnit_Framework_TestCase
{

    /**
     * data provider for printable bit value test
     *
     * @return array
     */
    public function printableBitValueDataProvider()
    {
        return array(
            array('testtest', 64, '0111010001100101011100110111010001110100011001010111001101110100'),
            array('test', 32, '01110100011001010111001101110100')
        );
    }

    /**
     * test for generating string contains printable bit value of selected data
     * @dataProvider printableBitValueDataProvider
     */

    public function testPrintableBitValue($a, $b, $e)
    {
        $this->assertEquals(
            $e, PMA_CommonFunctions::getInstance()->printableBitValue($a, $b)
        );
    }
}
?>
