<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Twig;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Twig\MessageExtension;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MessageExtension::class)]
final class MessageExtensionTest extends AbstractTestCase
{
    public function testGetNotice(): void
    {
        $message = 'Notice message';
        self::assertSame(Message::notice($message)->getDisplay(), MessageExtension::getNotice($message));
    }

    public function testGetError(): void
    {
        $message = 'Error message';
        self::assertSame(Message::error($message)->getDisplay(), MessageExtension::getError($message));
    }

    public function testGetRawSuccess(): void
    {
        $message = 'Success message';
        self::assertSame(Message::rawSuccess($message)->getDisplay(), MessageExtension::getRawSuccess($message));
    }

    public function testGetStatementMessage(): void
    {
        $message = 'Message';
        $statement = '';
        self::assertSame(
            Generator::getMessage($message, $statement, MessageType::Success),
            MessageExtension::getStatementMessage($message, $statement, 'success'),
        );
        self::assertSame(
            Generator::getMessage($message, $statement, MessageType::Error),
            MessageExtension::getStatementMessage($message, $statement, 'error'),
        );
        self::assertSame(
            Generator::getMessage($message, $statement, MessageType::Error),
            MessageExtension::getStatementMessage($message, $statement, 'danger'),
        );
        self::assertSame(
            Generator::getMessage($message, $statement),
            MessageExtension::getStatementMessage($message, $statement, 'notice'),
        );
        self::assertSame(
            Generator::getMessage($message, $statement),
            MessageExtension::getStatementMessage($message, $statement, 'unknown'),
        );
    }
}
