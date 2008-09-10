<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_pow()
 *
 * @version $Id: PMA_pow_test.php 10140 2007-03-20 08:32:55Z cybot_tm $
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/core.lib.php';

class PMA_isValid_test extends PHPUnit_Framework_TestCase
{
    public function testVarNotSetAfterTest()
    {
        PMA_isValid($var);
        $this->assertFalse(isset($var));
    }
    public function testNotSet()
    {
        $this->assertFalse(PMA_isValid($var));
    }
    public function testEmptyString()
    {
        $var = '';
        $this->assertFalse(PMA_isValid($var));
    }
    public function testNotEmptyString()
    {
        $var = '0';
        $this->assertTrue(PMA_isValid($var));
    }
    public function testZero()
    {
        $var = 0;
        $this->assertTrue(PMA_isValid($var));
    }
    public function testNullFail()
    {
        $var = null;
        $this->assertFalse(PMA_isValid($var));
    }
    public function testNotSetArray()
    {
        $this->assertFalse(PMA_isValid($array['x']));
    }
    public function testScalarString()
    {
        $var = 'string';
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }
    public function testScalarInt()
    {
        $var = 1;
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }
    public function testScalarFloat()
    {
        $var = 1.1;
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }
    public function testScalarBool()
    {
        $var = true;
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }
    public function testNotScalarArray()
    {
        $var = array('test');
        $this->assertFalse(PMA_isValid($var, 'scalar'));
    }
    public function testNotScalarNull()
    {
        $var = null;
        $this->assertFalse(PMA_isValid($var, 'scalar'));
    }
    public function testNumericInt()
    {
        $var = 1;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }
    public function testNumericFloat()
    {
        $var = 1.1;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }
    public function testNumericZero()
    {
        $var = 0;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }
    public function testNumericString()
    {
        $var = '+0.1';
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }
    public function testValueInArray()
    {
        $var = 'a';
        $this->assertTrue(PMA_isValid($var, array('a', 'b', )));
    }
    public function testValueNotInArray()
    {
        $var = 'c';
        $this->assertFalse(PMA_isValid($var, array('a', 'b', )));
    }
}
?>