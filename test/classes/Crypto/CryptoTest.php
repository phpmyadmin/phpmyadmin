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
        global $config;

        $_SESSION = [];
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        self::assertNotSame('test', $encrypted);
        self::assertSame('test', $crypto->decrypt($encrypted));
        self::assertArrayNotHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    public function testWithValidKeyFromSession(): void
    {
        global $config;

        $_SESSION = ['URLQueryEncryptionSecretKey' => str_repeat('a', 32)];
        $config->set('URLQueryEncryptionSecretKey', '');

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        self::assertNotSame('test', $encrypted);
        self::assertSame('test', $crypto->decrypt($encrypted));
        self::assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
    }

    public function testWithNewSessionKey(): void
    {
        global $config;

        $_SESSION = [];
        $config->set('URLQueryEncryptionSecretKey', '');

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        self::assertNotSame('test', $encrypted);
        self::assertSame('test', $crypto->decrypt($encrypted));
        self::assertArrayHasKey('URLQueryEncryptionSecretKey', $_SESSION);
        self::assertSame(32, mb_strlen($_SESSION['URLQueryEncryptionSecretKey'], '8bit'));
    }

    public function testDecryptWithInvalidKey(): void
    {
        global $config;

        $_SESSION = [];
        $config->set('URLQueryEncryptionSecretKey', str_repeat('a', 32));

        $crypto = new Crypto();
        $encrypted = $crypto->encrypt('test');
        self::assertNotSame('test', $encrypted);
        self::assertSame('test', $crypto->decrypt($encrypted));

        $config->set('URLQueryEncryptionSecretKey', str_repeat('b', 32));

        $crypto = new Crypto();
        self::assertNull($crypto->decrypt($encrypted));
    }
}
