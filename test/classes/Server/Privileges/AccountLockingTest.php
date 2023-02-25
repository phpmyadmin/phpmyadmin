<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Server\Privileges;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Server\Privileges\AccountLocking;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\TestCase;
use Throwable;

/** @covers \PhpMyAdmin\Server\Privileges\AccountLocking */
class AccountLockingTest extends TestCase
{
    public function testLockWithValidAccount(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->once())->method('getVersion')->willReturn(100402);
        $dbi->expects($this->exactly(2))->method('quoteString')
            ->will($this->returnCallback(static fn (string $string) => "'" . $string . "'"));
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($this->equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT LOCK;'))
            ->willReturn($this->createStub(DummyResult::class));
        $dbi->expects($this->never())->method('getError');

        $accountLocking = new AccountLocking($dbi);
        $accountLocking->lock('test.user', 'test.host');
    }

    public function testLockWithInvalidAccount(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->once())->method('getVersion')->willReturn(100402);
        $dbi->expects($this->exactly(2))->method('quoteString')
            ->will($this->returnCallback(static fn (string $string) => "'" . $string . "'"));
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($this->equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT LOCK;'))
            ->willReturn(false);
        $dbi->expects($this->once())->method('getError')->willReturn('Invalid account.');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Invalid account.');

        $accountLocking->lock('test.user', 'test.host');
    }

    public function testLockWithUnsupportedServer(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->once())->method('getVersion')->willReturn(100401);
        $dbi->expects($this->never())->method('quoteString');
        $dbi->expects($this->never())->method('tryQuery');
        $dbi->expects($this->never())->method('getError');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Account locking is not supported.');

        $accountLocking->lock('test.user', 'test.host');
    }

    public function testUnlockWithValidAccount(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->once())->method('getVersion')->willReturn(100402);
        $dbi->expects($this->exactly(2))->method('quoteString')
            ->will($this->returnCallback(static fn (string $string) => "'" . $string . "'"));
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($this->equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT UNLOCK;'))
            ->willReturn($this->createStub(DummyResult::class));
        $dbi->expects($this->never())->method('getError');

        $accountLocking = new AccountLocking($dbi);
        $accountLocking->unlock('test.user', 'test.host');
    }

    public function testUnlockWithInvalidAccount(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(true);
        $dbi->expects($this->once())->method('getVersion')->willReturn(100402);
        $dbi->expects($this->exactly(2))->method('quoteString')
            ->will($this->returnCallback(static fn (string $string) => "'" . $string . "'"));
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with($this->equalTo('ALTER USER \'test.user\'@\'test.host\' ACCOUNT UNLOCK;'))
            ->willReturn(false);
        $dbi->expects($this->once())->method('getError')->willReturn('Invalid account.');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Invalid account.');

        $accountLocking->unlock('test.user', 'test.host');
    }

    public function testUnlockWithUnsupportedServer(): void
    {
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('isMariaDB')->willReturn(false);
        $dbi->expects($this->once())->method('getVersion')->willReturn(50705);
        $dbi->expects($this->never())->method('quoteString');
        $dbi->expects($this->never())->method('tryQuery');
        $dbi->expects($this->never())->method('getError');

        $accountLocking = new AccountLocking($dbi);

        $this->expectException(Throwable::class);
        $this->expectExceptionMessage('Account locking is not supported.');

        $accountLocking->unlock('test.user', 'test.host');
    }
}
