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
    /**
     * to String casting test
     */
    public function testToString(): void
    {
        $message = new Message();
        $message->setMessage('test<&>');
        self::assertSame('test<&>', (string) $message);
    }

    /**
     * test success method
     */
    public function testSuccess(): void
    {
        $message = new Message('test<&>', MessageType::Success);
        self::assertEquals($message, Message::success('test<&>'));
        self::assertSame(
            'Your SQL query has been executed successfully.',
            Message::success()->getString(),
        );
    }

    /**
     * test error method
     */
    public function testError(): void
    {
        $message = new Message('test<&>', MessageType::Error);
        self::assertEquals($message, Message::error('test<&>'));
        self::assertSame('Error', Message::error()->getString());
    }

    /**
     * test notice method
     */
    public function testNotice(): void
    {
        $message = new Message('test<&>', MessageType::Notice);
        self::assertEquals($message, Message::notice('test<&>'));
    }

    /**
     * test rawError method
     */
    public function testRawError(): void
    {
        $message = new Message('', MessageType::Error);
        $message->setMessage('test<&>');
        $message->setBBCode(false);

        self::assertEquals($message, Message::rawError('test<&>'));
    }

    /**
     * test rawNotice method
     */
    public function testRawNotice(): void
    {
        $message = new Message('', MessageType::Notice);
        $message->setMessage('test<&>');
        $message->setBBCode(false);

        self::assertEquals($message, Message::rawNotice('test<&>'));
    }

    /**
     * test rawSuccess method
     */
    public function testRawSuccess(): void
    {
        $message = new Message('', MessageType::Success);
        $message->setMessage('test<&>');
        $message->setBBCode(false);

        self::assertEquals($message, Message::rawSuccess('test<&>'));
    }

    /**
     * testing isSuccess method
     */
    public function testIsSuccess(): void
    {
        $message = new Message();
        self::assertFalse($message->isSuccess());
        $message->setType(MessageType::Success);
        self::assertTrue($message->isSuccess());
    }

    /**
     * testing isNotice method
     */
    public function testIsNotice(): void
    {
        $message = new Message();
        self::assertTrue($message->isNotice());
        $message->setType(MessageType::Error);
        self::assertFalse($message->isNotice());
        $message->setType(MessageType::Notice);
        self::assertTrue($message->isNotice());
    }

    /**
     * testing isError method
     */
    public function testIsError(): void
    {
        $message = new Message();
        self::assertFalse($message->isError());
        $message->setType(MessageType::Error);
        self::assertTrue($message->isError());
    }

    /**
     * testing setter of message
     */
    public function testSetMessage(): void
    {
        $message = new Message();
        $message->setMessage('test&<>');
        self::assertSame('test&<>', $message->getMessage());
    }

    /**
     * testing add param method
     */
    public function testAddParam(): void
    {
        $message = new Message();
        $message->addParam(Message::notice('test'));
        self::assertEquals(
            [Message::notice('test')],
            $message->getParams(),
        );
        $message->addParam('test');
        self::assertEquals(
            [Message::notice('test'), 'test'],
            $message->getParams(),
        );
        $message->addParam('test');
        self::assertEquals(
            [Message::notice('test'), 'test', Message::notice('test')],
            $message->getParams(),
        );
    }

    /**
     * Test adding html markup
     */
    public function testAddParamHtml(): void
    {
        $message = new Message();
        $message->setMessage('Hello %s%s%s');
        $message->addParamHtml('<a href="">');
        $message->addParam('user<>');
        $message->addParamHtml('</a>');
        self::assertSame(
            'Hello <a href="">user&lt;&gt;</a>',
            $message->getMessage(),
        );
    }

    /**
     * testing add string method
     */
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

    /**
     * testing add message method
     */
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

    /**
     * testing add messages method
     */
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

    /**
     * testing add messages method
     */
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

    /**
     * getMessage test - with empty message and with non-empty string -
     * not key in globals additional params are defined
     */
    public function testGetMessageWithoutMessageWithStringWithParams(): void
    {
        $message = new Message('test string %s %s');
        $message->setMessage('');
        $message->addParam('test param 1');
        $message->addParam('test param 2');
        self::assertSame(
            'test string test param 1 test param 2',
            $message->getMessage(),
        );
    }

    /**
     * getMessage test - with empty message and with empty string
     */
    public function testGetMessageWithoutMessageWithEmptyString(): void
    {
        $message = new Message();
        self::assertSame('', $message->getMessage());
    }

    /**
     * getMessage test - message is defined
     * message with BBCode defined
     */
    public function testGetMessageWithMessageWithBBCode(): void
    {
        $message = new Message();
        $message->setMessage('[kbd]test[/kbd] [doc@cfg_Example]test[/doc]');
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

    /**
     * getDisplay test
     */
    public function testGetDisplay(): void
    {
        $message = new Message();
        self::assertFalse($message->isDisplayed());
        $message->setMessage('Test Message');
        self::assertSame(
            '<div class="alert alert-primary" role="alert">' . "\n"
            . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Test Message' . "\n"
            . '</div>' . "\n",
            $message->getDisplay(),
        );
        self::assertTrue($message->isDisplayed());
    }

    /**
     * isDisplayed test
     */
    public function testIsDisplayed(): void
    {
        $message = new Message();
        self::assertFalse($message->isDisplayed());
        $message->markDisplayed();
        self::assertTrue($message->isDisplayed());
        $message->markDisplayed();
        self::assertTrue($message->isDisplayed());
    }

    /**
     * Data provider for testAffectedRows
     *
     * @return array<int, array{int, string}> Test-data
     */
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

    /**
     * Test for getMessageForAffectedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     */
    #[DataProvider('providerAffectedRows')]
    public function testAffectedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForAffectedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }

    /**
     * Data provider for testInsertedRows
     *
     * @return array<int, array{int, string}> Test-data
     */
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

    /**
     * Test for getMessageForInsertedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     */
    #[DataProvider('providerInsertedRows')]
    public function testInsertedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForInsertedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }

    /**
     * Data provider for testDeletedRows
     *
     * @return array<int, array{int, string}> Test-data
     */
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

    /**
     * Test for getMessageForDeletedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     */
    #[DataProvider('providerDeletedRows')]
    public function testDeletedRows(int $rows, string $output): void
    {
        $message = new Message();
        $message->addMessage(Message::getMessageForDeletedRows($rows));
        self::assertSame($output, $message->getDisplay());
    }
}
