<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Crypto;

use PhpMyAdmin\Crypto\Crypto;
use PhpMyAdmin\Tests\AbstractTestCase;
use function str_repeat;
use function mb_strlen;

/**
 * @covers \PhpMyAdmin\Crypto\Crypto
 */
class CryptoTest extends AbstractTestCase
{
    public function testWithValidKeyFromConfig(): void
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

    public function testWithValidKeyFromSession(): void
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

    public function testWithNewSessionKey(): void
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

    public function testDecryptWithInvalidKey(): void
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
