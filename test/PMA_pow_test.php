<?php
/**
 * tests for PMA_pow()
 *
 * @version $Id: $
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

    public function testNegativeExp()
    {
        $this->assertEquals(false,
            PMA_pow(2, -1));
    }
}
?>