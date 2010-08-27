<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_sanitize()
 *
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/transformations.lib.php';

/**
 * @package phpMyAdmin-test
 */
class PMA_transformation_getOptions_test extends PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $this->assertEquals(array('option1 ', ' option2 '),
            PMA_transformation_getOptions("option1 , option2 "));
    }

    public function testQuoted()
    {
        $this->assertEquals(array('option1', ' option2'),
            PMA_transformation_getOptions("'option1' ,' option2' "));
    }

    public function testComma()
    {
        $this->assertEquals(array('2,3', ' ,, option ,,'),
            PMA_transformation_getOptions("'2,3' ,' ,, option ,,' "));
    }

    public function testEmptyOptions()
    {
        $this->assertEquals(array('', '', ''),
            PMA_transformation_getOptions("'',,"));
    }

    public function testEmpty()
    {
        $this->assertEquals(array(),
            PMA_transformation_getOptions(''));
    }
}
?>
