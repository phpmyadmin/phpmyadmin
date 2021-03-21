<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FlashMessages;
use RuntimeException;

class FlashMessagesTest extends AbstractTestCase
{
    private const STORAGE_KEY = 'flashMessages';

    public function testConstructor(): void
    {
        $this->assertArrayNotHasKey(self::STORAGE_KEY, $_SESSION);
        $flash = new FlashMessages();
        $this->assertIsArray($_SESSION[self::STORAGE_KEY]);

        $_SESSION = null;
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Session not found.');
        $flash = new FlashMessages();
    }

    public function testAddMessage(): void
    {
        $flash = new FlashMessages();
        $this->assertArrayNotHasKey('error', $_SESSION[self::STORAGE_KEY]);
        $flash->addMessage('error', 'Error');
        $this->assertArrayHasKey('error', $_SESSION[self::STORAGE_KEY]);
        $this->assertIsArray($_SESSION[self::STORAGE_KEY]['error']);
        $this->assertEquals(['Error'], $_SESSION[self::STORAGE_KEY]['error']);
    }

    public function testGetMessage(): void
    {
        $_SESSION[self::STORAGE_KEY] = ['warning' => ['Warning']];
        $flash = new FlashMessages();
        $message = $flash->getMessage('error');
        $this->assertNull($message);
        $message = $flash->getMessage('warning');
        $this->assertEquals(['Warning'], $message);
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
        $this->assertEquals(
            [
                'error' => ['Error1', 'Error2'],
                'warning' => ['Warning'],
            ],
            $messages
        );
    }
}
