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

    public function testSessionNotFoundException(): void
    {
        $_SESSION = null;
        $flashMessenger = new FlashMessenger();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session not found.');
        $flashMessenger->getCurrentMessages();
    }

    public function testAddMessage(): void
    {
        unset($_SESSION[self::STORAGE_KEY]);
        $flashMessenger = new FlashMessenger();
        self::assertArrayNotHasKey(self::STORAGE_KEY, $_SESSION);
        $flashMessenger->addMessage('error', 'Error');
        self::assertSame(
            [['context' => 'error', 'message' => 'Error', 'statement' => '']],
            $_SESSION[self::STORAGE_KEY],
        );
    }

    public function testAddMessageWithStatement(): void
    {
        unset($_SESSION[self::STORAGE_KEY]);
        $flashMessenger = new FlashMessenger();
        self::assertArrayNotHasKey(self::STORAGE_KEY, $_SESSION);
        $flashMessenger->addMessage('success', 'Success!', 'SELECT 1;');
        self::assertSame(
            [['context' => 'success', 'message' => 'Success!', 'statement' => 'SELECT 1;']],
            $_SESSION[self::STORAGE_KEY],
        );
    }

    public function testGetMessages(): void
    {
        unset($_SESSION[self::STORAGE_KEY]);
        $flashMessengerOne = new FlashMessenger();
        self::assertSame([], $flashMessengerOne->getCurrentMessages());
        $flashMessengerOne->addMessage('error', 'Error1');
        $flashMessengerOne->addMessage('error', 'Error2', 'SOME SQL;');
        $flashMessengerOne->addMessage('warning', 'Warning');
        self::assertSame(
            [
                ['context' => 'error', 'message' => 'Error1', 'statement' => ''],
                ['context' => 'error', 'message' => 'Error2', 'statement' => 'SOME SQL;'],
                ['context' => 'warning', 'message' => 'Warning', 'statement' => ''],
            ],
            $flashMessengerOne->getCurrentMessages(),
        );
        self::assertSame([], $flashMessengerOne->getMessages());
        $flashMessengerTwo = new FlashMessenger();
        $flashMessengerTwo->addMessage('notice', 'Notice');
        self::assertSame(
            [['context' => 'notice', 'message' => 'Notice', 'statement' => '']],
            $flashMessengerTwo->getCurrentMessages(),
        );
        self::assertSame(
            [
                ['context' => 'error', 'message' => 'Error1', 'statement' => ''],
                ['context' => 'error', 'message' => 'Error2', 'statement' => 'SOME SQL;'],
                ['context' => 'warning', 'message' => 'Warning', 'statement' => ''],
            ],
            $flashMessengerTwo->getMessages(),
        );
        self::assertSame(
            [['context' => 'notice', 'message' => 'Notice', 'statement' => '']],
            (new FlashMessenger())->getMessages(),
        );
    }
}
