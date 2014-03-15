<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_ifSetOr() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

/**
 * Tests for PMA_ifSetOr() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_IfSetOr_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_ifSetOr
     *
     * @return void
     */
    public function testVarSet()
    {
        $default = 'foo';
        $in = 'bar';
        $out = PMA_ifSetOr($in, $default);
        $this->assertEquals($in, $out);
    }

    /**
     * Test for PMA_ifSetOr
     *
     * @return void
     */
    public function testVarSetWrongType()
    {
        $default = 'foo';
        $in = 'bar';
        $out = PMA_ifSetOr($in, $default, 'boolean');
        $this->assertEquals($out, $default);
    }

    /**
     * Test for PMA_ifSetOr
     *
     * @return void
     */
    public function testVarNotSet()
    {
        $default = 'foo';
        // $in is not set!
        $out = PMA_ifSetOr($in, $default);
        $this->assertEquals($out, $default);
    }

    /**
     * Test for PMA_ifSetOr
     *
     * @return void
     */
    public function testVarNotSetNoDefault()
    {
        // $in is not set!
        $out = PMA_ifSetOr($in);
        $this->assertEquals($out, null);
    }

}
?>
