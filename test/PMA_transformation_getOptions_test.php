<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_sanitize()
 *
 * @version $Id: PMA_sanitize_test.php 10140 2007-03-20 08:32:55Z cybot_tm $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/transformations.lib.php';

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