<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversClass(Session::class)]
final class SessionTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSecure(): void
    {
        $_SESSION[' PMA_token '] = null;
        $_SESSION[' HMAC_secret '] = null;

        Session::secure();

        /** @psalm-suppress TypeDoesNotContainType */
        self::assertIsString($_SESSION[' PMA_token ']);
        self::assertNotEmpty($_SESSION[' PMA_token ']);
        /** @psalm-suppress TypeDoesNotContainType */
        self::assertIsString($_SESSION[' HMAC_secret ']);
        self::assertNotEmpty($_SESSION[' HMAC_secret ']);
    }
}
