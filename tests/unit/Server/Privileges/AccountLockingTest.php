<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\Privileges;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(AccountLocking::class)]
class AccountLockingTest extends TestCase
{
    public function testLockWithValidAccount(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);
        $dbi->expects(self::once())->method('getVersion')->willReturn(100402);
        $dbi->expects(self::exactly(2))->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with(self::equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT LOCK;'))
            ->willReturn(self::createStub(DummyResult::class));
        $dbi->expects(self::never())->method('getError');

        $accountLocking = new AccountLocking($dbi);
        $accountLocking->lock('test.user', 'test.host');
    }

    public function testLockWithInvalidAccount(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);
        $dbi->expects(self::once())->method('getVersion')->willReturn(100402);
        $dbi->expects(self::exactly(2))->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with(self::equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT LOCK;'))
            ->willReturn(false);
        $dbi->expects(self::once())->method('getError')->willReturn('Invalid account.');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Invalid account.');

        $accountLocking->lock('test.user', 'test.host');
    }

    public function testLockWithUnsupportedServer(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);
        $dbi->expects(self::once())->method('getVersion')->willReturn(100401);
        $dbi->expects(self::never())->method('quoteString');
        $dbi->expects(self::never())->method('tryQuery');
        $dbi->expects(self::never())->method('getError');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Account locking is not supported.');

        $accountLocking->lock('test.user', 'test.host');
    }

    public function testUnlockWithValidAccount(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);
        $dbi->expects(self::once())->method('getVersion')->willReturn(100402);
        $dbi->expects(self::exactly(2))->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with(self::equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT UNLOCK;'))
            ->willReturn(self::createStub(DummyResult::class));
        $dbi->expects(self::never())->method('getError');

        $accountLocking = new AccountLocking($dbi);
        $accountLocking->unlock('test.user', 'test.host');
    }

    public function testUnlockWithInvalidAccount(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(true);
        $dbi->expects(self::once())->method('getVersion')->willReturn(100402);
        $dbi->expects(self::exactly(2))->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with(self::equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT UNLOCK;'))
            ->willReturn(false);
        $dbi->expects(self::once())->method('getError')->willReturn('Invalid account.');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Invalid account.');

        $accountLocking->unlock('test.user', 'test.host');
    }

    public function testUnlockWithUnsupportedServer(): void
    {
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('isMariaDB')->willReturn(false);
        $dbi->expects(self::once())->method('getVersion')->willReturn(50705);
        $dbi->expects(self::never())->method('quoteString');
        $dbi->expects(self::never())->method('tryQuery');
        $dbi->expects(self::never())->method('getError');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Account locking is not supported.');

        $accountLocking->unlock('test.user', 'test.host');
    }
}
