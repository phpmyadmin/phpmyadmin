<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Message::class)]
class MessageTest extends AbstractTestCase
{
    public function testToString(): void
    {
        $message = Message::raw('test<&>');
        self::assertSame('test<&>', (string) $message);
    }

    public function testSuccess(): void
    {
        $message = new Message('test<&>', MessageType::Success);
        self::assertEquals($message, Message::success('test<&>'));
        self::assertSame(
            'Your SQL query has been executed successfully.',
            Message::success()->getString(),
        );
    }

    public function testError(): void
    {
        $message = new Message('test<&>', MessageType::Error);
        self::assertEquals($message, Message::error('test<&>'));
        self::assertSame('Error', Message::error()->getString());
    }

    public function testNotice(): void
    {
        $message = new Message('test<&>', MessageType::Notice);
        self::assertEquals($message, Message::notice('test<&>'));
    }

    public function testRawError(): void
    {
        $message = Message::raw('test<&>', MessageType::Error);

        self::assertEquals($message, Message::rawError('test<&>'));
    }

    public function testRawNotice(): void
    {
        $message = Message::raw('test<&>', MessageType::Notice);
        self::assertEquals($message, Message::rawNotice('test<&>'));
    }

    public function testRawSuccess(): void
    {
        $message = Message::raw('test<&>', MessageType::Success);
        self::assertEquals($message, Message::rawSuccess('test<&>'));
    }

    public function testIsSuccess(): void
    {
        $message = new Message();
        self::assertFalse($message->isSuccess());
        $message->setType(MessageType::Success);
        self::assertTrue($message->isSuccess());
    }

    public function testIsNotice(): void
    {
        $message = new Message();
        self::assertTrue($message->isNotice());
        $message->setType(MessageType::Error);
        self::assertFalse($message->isNotice());
        $message->setType(MessageType::Notice);
        self::assertTrue($message->isNotice());
    }

    public function testIsError(): void
    {
        $message = new Message();
        self::assertFalse($message->isError());
        $message->setType(MessageType::Error);
        self::assertTrue($message->isError());
    }

    public function testAddParam(): void
    {
        $message = new Message('m1 %s %s %s', params: ['param1']);
        $message->addParam('param2');
        $message->addParam(Message::notice('m2 %s', ['param3']));
        self::assertSame(
            'm1 param1 param2 m2 param3',
            $message->getMessage(),
        );
    }

    public function testAddParamHtml(): void
    {
        $message = Message::raw('Hello %s%s%s');
        $message->addParamHtml('<a href="">');
        $message->addParam('user<>');
        $message->addParamHtml('</a>');
        self::assertSame(
            'Hello <a href="">user&lt;&gt;</a>',
            $message->getMessage(),
        );
    }

    public function testAddString(): void
    {
        $message = new Message();
        $message->addText('test', '*');
        self::assertSame(
            '*test',
            $message->getMessage(),
        );
        $message->addText('test', '');
        self::assertSame(
            '*testtest',
            $message->getMessage(),
        );
    }

    public function testAddMessage(): void
    {
        $message = new Message();
        $message->addText('test<>', '');
        self::assertSame(
            'test&lt;&gt;',
            $message->getMessage(),
        );
        $message->addHtml('<b>test</b>');
        self::assertSame(
            'test&lt;&gt; <b>test</b>',
            $message->getMessage(),
        );
        $message->addMessage(Message::notice('test<>'));
        self::assertSame(
            'test&lt;&gt; <b>test</b> test<>',
            $message->getMessage(),
        );
    }

    public function testAddMessages(): void
    {
        $messages = [];
        $messages[] = new Message('Test1');
        $messages[] = new Message('PMA_Test2', MessageType::Error);
        $messages[] = new Message('Test3');
        $message = new Message();
        $message->addMessages($messages, '');

        self::assertSame(
            'Test1PMA_Test2Test3',
            $message->getMessage(),
        );
    }

    public function testAddMessagesString(): void
    {
        $messages = ['test1', 'test<b>', 'test2'];
        $message = new Message();
        $message->addMessagesString($messages, '');

        self::assertSame(
            'test1test&lt;b&gt;test2',
            $message->getMessage(),
        );

        self::assertSame(
            'test1test&lt;b&gt;test2',
            $message->getMessage(),
        );
    }

    public function testGetMessageWithoutMessageWithStringWithParams(): void
    {
        $message = new Message('test string %s %s');
        $message->addParam('test param 1');
        $message->addParam('test param 2');
        self::assertSame(
            'test string test param 1 test param 2',
            $message->getMessage(),
        );
    }

    public function testGetMessageWithoutMessageWithEmptyString(): void
    {
        $message = new Message();
        self::assertSame('', $message->getMessage());
    }

    public function testGetMessageWithBBCode(): void
    {
        $message = new Message('[kbd]test[/kbd] [doc@cfg_Example]test[/doc]');
        self::assertSame(
            '<kbd>test</kbd> <a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.'
            . 'net%2Fen%2Flatest%2Fconfig.html%23cfg_Example"'
            . ' target="documentation">test</a>',
            $message->getMessage(),
        );
    }

    public function testGetContext(): void
    {
        $message = new Message();
        self::assertSame('primary', $message->getContext());
        $message->setType(MessageType::Success);
        self::assertSame('success', $message->getContext());
        $message->setType(MessageType::Error);
        self::assertSame('danger', $message->getContext());
    }

    public function testGetDisplay(): void
    {
        $message = Message::raw('Test Message');
        self::assertSame(
            '<div class="alert alert-primary" role="alert">' . "\n"
            . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Test Message' . "\n"
            . '</div>' . "\n",
            $message->getDisplay(),
        );
    }

    /** @return array<int, array{int, string}> Test-data */
    public static function providerAffectedRows(): array
    {
        return [
            [
                1,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  1 row affected.' . "\n"
                . '</div>' . "\n",
            ],
            [
                2,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  2 rows affected.' . "\n"
                . '</div>' . "\n",
            ],
            [
                10000,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  10000 rows affected.' . "\n"
                . '</div>' . "\n",
            ],
        ];
    }

    #[DataProvider('providerAffectedRows')]
    public function testAffectedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForAffectedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }

    /** @return array<int, array{int, string}> Test-data */
    public static function providerInsertedRows(): array
    {
        return [
            [
                1,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  1 row inserted.' . "\n"
                . '</div>' . "\n",
            ],
            [
                2,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  2 rows inserted.' . "\n"
                . '</div>' . "\n",
            ],
            [
                100000,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  100000 rows inserted.' . "\n"
                . '</div>' . "\n",
            ],
        ];
    }

    #[DataProvider('providerInsertedRows')]
    public function testInsertedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForInsertedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }

    /** @return array<int, array{int, string}> Test-data */
    public static function providerDeletedRows(): array
    {
        return [
            [
                1,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  1 row deleted.' . "\n"
                . '</div>' . "\n",
            ],
            [
                2,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  2 rows deleted.' . "\n"
                . '</div>' . "\n",
            ],
            [
                500000,
                '<div class="alert alert-primary" role="alert">' . "\n"
                . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice">  500000 rows deleted.' . "\n"
                . '</div>' . "\n",
            ],
        ];
    }

    #[DataProvider('providerDeletedRows')]
    public function testDeletedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForDeletedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }
}
