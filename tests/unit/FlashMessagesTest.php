<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FlashMessages;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(FlashMessages::class)]
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
        self::assertSame([['message' => 'Error', 'statement' => '']], $_SESSION[self::STORAGE_KEY]['error']);
    }

    public function testAddMessageWithStatement(): void
    {
        $flash = new FlashMessages();
        self::assertArrayNotHasKey('success', $_SESSION[self::STORAGE_KEY]);
        $flash->addMessage('success', 'Your SQL query has been executed successfully.', 'SELECT 1;');
        self::assertArrayHasKey('success', $_SESSION[self::STORAGE_KEY]);
        self::assertIsArray($_SESSION[self::STORAGE_KEY]['success']);
        self::assertSame(
            [['message' => 'Your SQL query has been executed successfully.', 'statement' => 'SELECT 1;']],
            $_SESSION[self::STORAGE_KEY]['success'],
        );
    }

    public function testGetMessage(): void
    {
        $_SESSION[self::STORAGE_KEY] = ['warning' => [['message' => 'Warning', 'statement' => '']]];
        $flash = new FlashMessages();
        $message = $flash->getMessage('error');
        self::assertNull($message);
        $message = $flash->getMessage('warning');
        self::assertSame([['message' => 'Warning', 'statement' => '']], $message);
    }

    public function testGetMessages(): void
    {
        $_SESSION[self::STORAGE_KEY] = [
            'error' => [
                ['message' => 'Error1', 'statement' => ''],
                ['message' => 'Error2', 'statement' => ''],
            ],
            'warning' => [['message' => 'Warning', 'statement' => '']],
        ];
        $flash = new FlashMessages();
        $flash->addMessage('notice', 'Notice');
        $messages = $flash->getMessages();
        self::assertSame(
            [
                'error' => [
                    ['message' => 'Error1', 'statement' => ''],
                    ['message' => 'Error2', 'statement' => ''],
                ],
                'warning' => [['message' => 'Warning', 'statement' => '']],
            ],
            $messages,
        );
    }
}
