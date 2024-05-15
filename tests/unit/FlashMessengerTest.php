<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FlashMessenger;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(FlashMessenger::class)]
final class FlashMessengerTest extends AbstractTestCase
{
    private const STORAGE_KEY = 'FlashMessenger';

    public function testConstructor(): void
    {
        self::assertArrayNotHasKey(self::STORAGE_KEY, $_SESSION);
        $flashMessenger = new FlashMessenger();
        self::assertIsArray($_SESSION[self::STORAGE_KEY]);
        self::assertSame([], $flashMessenger->getMessages());
    }

    public function testConstructorSessionNotFound(): void
    {
        $_SESSION = null;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session not found.');
        new FlashMessenger();
    }

    public function testAddMessage(): void
    {
        $flashMessenger = new FlashMessenger();
        self::assertArrayNotHasKey('error', $_SESSION[self::STORAGE_KEY]);
        $flashMessenger->addMessage('error', 'Error');
        self::assertArrayHasKey('error', $_SESSION[self::STORAGE_KEY]);
        self::assertIsArray($_SESSION[self::STORAGE_KEY]['error']);
        self::assertSame([['message' => 'Error', 'statement' => '']], $_SESSION[self::STORAGE_KEY]['error']);
    }

    public function testAddMessageWithStatement(): void
    {
        $flashMessenger = new FlashMessenger();
        self::assertArrayNotHasKey('success', $_SESSION[self::STORAGE_KEY]);
        $flashMessenger->addMessage('success', 'Your SQL query has been executed successfully.', 'SELECT 1;');
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
        $flashMessenger = new FlashMessenger();
        $message = $flashMessenger->getMessage('error');
        self::assertNull($message);
        $message = $flashMessenger->getMessage('warning');
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
        $flashMessenger = new FlashMessenger();
        $flashMessenger->addMessage('notice', 'Notice');
        $messages = $flashMessenger->getMessages();
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
