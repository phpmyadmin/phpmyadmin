<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FlashMessages;
use RuntimeException;

/**
 * @covers \PhpMyAdmin\FlashMessages
 */
class FlashMessagesTest extends AbstractTestCase
{
    private const STORAGE_KEY = 'flashMessages';

    public function testConstructor(): void
    {
        self::assertArrayNotHasKey(self::STORAGE_KEY, $_SESSION);
        $flash = new FlashMessages();
        self::assertIsArray($_SESSION[self::STORAGE_KEY]);
        self::assertSame([], $flash->getMessages());
    }

    public function testConstructorSessionNotFound(): void
    {
        $_SESSION = null;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session not found.');
        new FlashMessages();
    }

    public function testAddMessage(): void
    {
        $flash = new FlashMessages();
        self::assertArrayNotHasKey('error', $_SESSION[self::STORAGE_KEY]);
        $flash->addMessage('error', 'Error');
        self::assertArrayHasKey('error', $_SESSION[self::STORAGE_KEY]);
        self::assertIsArray($_SESSION[self::STORAGE_KEY]['error']);
        self::assertSame(['Error'], $_SESSION[self::STORAGE_KEY]['error']);
    }

    public function testGetMessage(): void
    {
        $_SESSION[self::STORAGE_KEY] = ['warning' => ['Warning']];
        $flash = new FlashMessages();
        $message = $flash->getMessage('error');
        self::assertNull($message);
        $message = $flash->getMessage('warning');
        self::assertSame(['Warning'], $message);
    }

    public function testGetMessages(): void
    {
        $_SESSION[self::STORAGE_KEY] = [
            'error' => ['Error1', 'Error2'],
            'warning' => ['Warning'],
        ];
        $flash = new FlashMessages();
        $flash->addMessage('notice', 'Notice');
        $messages = $flash->getMessages();
        self::assertSame([
            'error' => ['Error1', 'Error2'],
            'warning' => ['Warning'],
        ], $messages);
    }
}
