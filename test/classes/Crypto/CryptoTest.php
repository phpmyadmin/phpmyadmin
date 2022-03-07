<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Crypto;

use PhpMyAdmin\Crypto\Crypto;
use PhpMyAdmin\Tests\AbstractTestCase;

use function mb_strlen;
use function str_repeat;

/**
 * @covers \PhpMyAdmin\Crypto\Crypto
 */
class CryptoTest extends AbstractTestCase
{
    public function testWithValidKeyFromConfig(): void
    {
        $_SESSION = [];
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $crypto->decrypt($encrypted));
        $this->assertArrayNotHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    public function testWithValidKeyFromSession(): void
    {
        $_SESSION = ['URLQueryEncryptionSecretKey' => str_repeat('a', 32)];
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', '');

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $crypto->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    public function testWithNewSessionKey(): void
    {
        $_SESSION = [];
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', '');

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $crypto->decrypt($encrypted));
        $this->assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
        $this->assertEquals(32, mb_strlen($_SESSION['URLQueryEncryptionSecretKey'], '8bit'));
    }

    public function testDecryptWithInvalidKey(): void
    {
        $_SESSION = [];
        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        $this->assertNotSame('test', $encrypted);
        $this->assertSame('test', $crypto->decrypt($encrypted));

        $GLOBALS['config']->set('URLQueryEncryptionSecretKey', str_repeat('b', 32));

        $crypto = new Crypto();
        $this->assertNull($crypto->decrypt($encrypted));
    }
}
