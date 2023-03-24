<?php

namespace PhpMyAdmin\Tests\Crypto;

use PhpMyAdmin\Crypto\Crypto;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * @covers \PhpMyAdmin\Crypto\Crypto
 */
class CryptoTest extends PmaTestCase
{
    /**
     * @return void
     */
    public function testWithValidKeyFromConfig()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $cryptoWithSodium = new Crypto();
        $encrypted = $cryptoWithSodium->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithSodium->decrypt($encrypted));
        $this->assertArrayNotHasKey('URLQueryEncryptionSecretKey', $_SESSION);

        $cryptoWithPhpseclib = new Crypto(true);
        $encrypted = $cryptoWithPhpseclib->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithPhpseclib->decrypt($encrypted));
        $this->assertArrayNotHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    /**
     * @return void
     */
    public function testWithValidKeyFromSession()
    {
        global $PMA_Config;

        $_SESSION = ['URLQueryEncryptionSecretKey' => str_repeat('a', 32)];
        $PMA_Config->set('URLQueryEncryptionSecretKey', '');

        $cryptoWithSodium = new Crypto();
        $encrypted = $cryptoWithSodium->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithSodium->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);

        $cryptoWithPhpseclib = new Crypto(true);
        $encrypted = $cryptoWithPhpseclib->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithPhpseclib->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    /**
     * @return void
     */
    public function testWithNewSessionKey()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryptionSecretKey', '');

        $cryptoWithSodium = new Crypto();
        $encrypted = $cryptoWithSodium->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithSodium->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
        $this->assertEquals(32, mb_strlen($_SESSION['URLQueryEncryptionSecretKey'], '8bit'));

        $cryptoWithPhpseclib = new Crypto(true);
        $encrypted = $cryptoWithPhpseclib->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithPhpseclib->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
        $this->assertEquals(32, mb_strlen($_SESSION['URLQueryEncryptionSecretKey'], '8bit'));
    }

    /**
     * @return void
     */
    public function testDecryptWithInvalidKey()
    {
        global $PMA_Config;

        $_SESSION = [];
        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $cryptoWithSodium = new Crypto();
        $encrypted = $cryptoWithSodium->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithSodium->decrypt($encrypted));

        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('b', 32));

        $cryptoWithSodium = new Crypto();
        $this->assertNull($cryptoWithSodium->decrypt($encrypted));

        $cryptoWithPhpseclib = new Crypto(true);
        $encrypted = $cryptoWithPhpseclib->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $cryptoWithPhpseclib->decrypt($encrypted));

        $PMA_Config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $cryptoWithPhpseclib = new Crypto(true);
        $this->assertNull($cryptoWithPhpseclib->decrypt($encrypted));
    }
}
