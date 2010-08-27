<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_ifSetOr()
 *
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/core.lib.php';

/**
 * @package phpMyAdmin-test
 */
class PMA_ifSetOr_test extends PHPUnit_Framework_TestCase
{
    public function testVarSet()
    {
        $default = 'foo';
        $in = 'bar';
        $out = PMA_ifSetOr($in, $default);
        $this->assertEquals($in, $out);
    }
    public function testVarSetWrongType()
    {
        $default = 'foo';
        $in = 'bar';
        $out = PMA_ifSetOr($in, $default, 'boolean');
        $this->assertEquals($out, $default);
    }
    public function testVarNotSet()
    {
        $default = 'foo';
        // $in is not set!
        $out = PMA_ifSetOr($in, $default);
        $this->assertEquals($out, $default);
    }
    public function testVarNotSetNoDefault()
    {
        // $in is not set!
        $out = PMA_ifSetOr($in);
        $this->assertEquals($out, null);
    }

}
?>
