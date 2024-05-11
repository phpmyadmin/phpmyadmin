<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Exceptions;

use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthenticationFailure::class)]
final class AuthenticationFailureTest extends TestCase
{
    public function testAllowDenied(): void
    {
        $exception = AuthenticationFailure::deniedByAllowDenyRules();
        self::assertSame('allow-denied', $exception->failureType);
        self::assertSame('Access denied!', $exception->getMessage());
    }

    public function testEmptyDenied(): void
    {
        $exception = AuthenticationFailure::emptyPasswordDeniedByConfiguration();
        self::assertSame('empty-denied', $exception->failureType);
        self::assertSame(
            'Login without a password is forbidden by configuration (see AllowNoPassword).',
            $exception->getMessage(),
        );
    }

    public function testNoActivity(): void
    {
        $exception = AuthenticationFailure::loggedOutDueToInactivity();
        self::assertSame('no-activity', $exception->failureType);
        self::assertSame(
            'You have been automatically logged out due to inactivity of %s seconds.'
            . ' Once you log in again, you should be able to resume the work where you left off.',
            $exception->getMessage(),
        );
    }

    public function testRootDenied(): void
    {
        $exception = AuthenticationFailure::rootDeniedByConfiguration();
        self::assertSame('root-denied', $exception->failureType);
        self::assertSame('Access denied!', $exception->getMessage());
    }

    public function testServerDenied(): void
    {
        $exception = AuthenticationFailure::deniedByDatabaseServer();
        self::assertSame('server-denied', $exception->failureType);
        self::assertSame('Cannot log in to the database server.', $exception->getMessage());
    }
}
