<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for blowfish encryption.
 *
 * @package phpMyAdmin-test
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/blowfish.php';

/**
 * Test java script escaping.
 *
 * @package phpMyAdmin-test
 */
class PMA_blowfish_test extends PHPUnit_Framework_TestCase
{
    public function testEncryptDecryptNumbers()
    {
        $secret = '$%ÄüfuDFRR';
        $string = '12345678';
        $this->assertEquals($string,
            PMA_blowfish_decrypt(PMA_blowfish_encrypt($string, $secret), $secret));
    }

    public function testEncryptDecryptChars()
    {
        $secret = '$%ÄüfuDFRR';
        $string = 'abcDEF012!"§$%&/()=?`´"\',.;:-_#+*~öäüÖÄÜ^°²³';
        $this->assertEquals($string,
            PMA_blowfish_decrypt(PMA_blowfish_encrypt($string, $secret), $secret));
    }

    public function testEncrypt()
    {
        $secret = '$%ÄüfuDFRR';
        $decrypted = '12345678';
        $encrypted = 'kO/kc4j/nyk=';
        $this->assertEquals($encrypted, PMA_blowfish_encrypt($decrypted, $secret));
    }

    public function testDecrypt()
    {
        $secret = '$%ÄüfuDFRR';
        $encrypted = 'kO/kc4j/nyk=';
        $decrypted = '12345678';
        $this->assertEquals($decrypted, PMA_blowfish_decrypt($encrypted, $secret));
    }

}
?>
