<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_Util::pow() function from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';

class PMA_pow_test extends PHPUnit_Framework_TestCase
{
    public function testIntOverflow()
    {
        $this->assertEquals(
            '1267650600228229401496703205376',
            PMA_Util::pow(2, 100)
        );
    }

    public function testBcpow()
    {
        if (function_exists('bcpow')) {
            $this->assertEquals(
                '1267650600228229401496703205376',
                PMA_Util::pow(2, 100, 'bcpow')
            );
        } else {
            $this->markTestSkipped('function bcpow() does not exist');
        }
    }

    public function testGmppow()
    {
        if (function_exists('gmp_pow')) {
            $this->assertEquals(
                '1267650600228229401496703205376',
                PMA_Util::pow(2, 100, 'gmp_pow')
            );
        } else {
            $this->markTestSkipped('function gmp_pow() does not exist');
        }
    }

    public function testNegativeExp()
    {
        $this->assertEquals(
            0.25,
            PMA_Util::pow(2, -2)
        );
    }

    public function testNegativeExpPow()
    {
        if (function_exists('pow')) {
            $this->assertEquals(
                0.25,
                PMA_Util::pow(2, -2, 'pow')
            );
        } else {
            $this->markTestSkipped('function pow() does not exist');
        }
    }

    public function testNegativeExpBcpow()
    {
        if (function_exists('bcpow')) {
            $this->assertEquals(
                false,
                PMA_Util::pow(2, -2, 'bcpow')
            );
        } else {
            $this->markTestSkipped('function bcpow() does not exist');
        }
    }

    public function testNegativeExpGmppow()
    {
        if (function_exists('gmp_pow')) {
            $this->assertEquals(
                false,
                PMA_Util::pow(2, -2, 'gmp_pow')
            );
        } else {
            $this->markTestSkipped('function gmp_pow() does not exist');
        }
    }
}
?>
