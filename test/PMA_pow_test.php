<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_pow()
 *
 * @version $Id$
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'PHPUnit/Framework.php';
require_once './libraries/common.lib.php';

class PMA_pow_test extends PHPUnit_Framework_TestCase
{
    public function testIntOverflow()
    {
        $this->assertEquals('1267650600228229401496703205376',
            PMA_pow(2, 100));
    }

    public function testBcpow()
    {
        if (function_exists('bcpow')) {
            $this->assertEquals('1267650600228229401496703205376',
                PMA_pow(2, 100, 'bcpow'));
        } else {
            $this->markTestSkipped('function bcpow() does not exist');
        }
    }

    public function testGmppow()
    {
        if (function_exists('gmp_pow')) {
            $this->assertEquals('1267650600228229401496703205376',
                PMA_pow(2, 100, 'gmp_pow'));
        } else {
            $this->markTestSkipped('function gmp_pow() does not exist');
        }
    }

    public function _testNegativeExp()
    {
        $this->assertEquals(0.25,
            PMA_pow(2, -2));
    }

    public function _testNegativeExpPow()
    {
        if (function_exists('pow')) {
            $this->assertEquals(0.25,
                PMA_pow(2, -2, 'pow'));
        } else {
            $this->markTestSkipped('function pow() does not exist');
        }
    }

    public function _testNegativeExpBcpow()
    {
        if (function_exists('bcpow')) {
            $this->assertEquals(0.25,
                PMA_pow(2, -2, 'bcpow'));
        } else {
            $this->markTestSkipped('function bcpow() does not exist');
        }
    }

    public function _testNegativeExpGmppow()
    {
        if (function_exists('gmp_pow')) {
            $this->assertEquals(0.25,
                PMA_pow(2, -2, 'gmp_pow'));
        } else {
            $this->markTestSkipped('function gmp_pow() does not exist');
        }
    }
}
?>