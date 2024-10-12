<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Message;

use function md5;

/**
 * @covers \PhpMyAdmin\Message
 */
class MessageTest extends AbstractTestCase
{
    /** @var Message */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new Message();
    }

    /**
     * to String casting test
     */
    public function testToString(): void
    {
        $this->object->setMessage('test<&>', true);
        self::assertSame('test&lt;&amp;&gt;', (string) $this->object);
    }

    /**
     * test success method
     */
    public function testSuccess(): void
    {
        $this->object = new Message('test<&>', Message::SUCCESS);
        self::assertEquals($this->object, Message::success('test<&>'));
        self::assertSame('Your SQL query has been executed successfully.', Message::success()->getString());
    }

    /**
     * test error method
     */
    public function testError(): void
    {
        $this->object = new Message('test<&>', Message::ERROR);
        self::assertEquals($this->object, Message::error('test<&>'));
        self::assertSame('Error', Message::error()->getString());
    }

    /**
     * test notice method
     */
    public function testNotice(): void
    {
        $this->object = new Message('test<&>', Message::NOTICE);
        self::assertEquals($this->object, Message::notice('test<&>'));
    }

    /**
     * test rawError method
     */
    public function testRawError(): void
    {
        $this->object = new Message('', Message::ERROR);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        self::assertEquals($this->object, Message::rawError('test<&>'));
    }

    /**
     * test rawNotice method
     */
    public function testRawNotice(): void
    {
        $this->object = new Message('', Message::NOTICE);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        self::assertEquals($this->object, Message::rawNotice('test<&>'));
    }

    /**
     * test rawSuccess method
     */
    public function testRawSuccess(): void
    {
        $this->object = new Message('', Message::SUCCESS);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        self::assertEquals($this->object, Message::rawSuccess('test<&>'));
    }

    /**
     * testing isSuccess method
     */
    public function testIsSuccess(): void
    {
        self::assertFalse($this->object->isSuccess());
        self::assertTrue($this->object->isSuccess(true));
    }

    /**
     * testing isNotice method
     */
    public function testIsNotice(): void
    {
        self::assertTrue($this->object->isNotice());
        $this->object->isError(true);
        self::assertFalse($this->object->isNotice());
        self::assertTrue($this->object->isNotice(true));
    }

    /**
     * testing isError method
     */
    public function testIsError(): void
    {
        self::assertFalse($this->object->isError());
        self::assertTrue($this->object->isError(true));
    }

    /**
     * testing setter of message
     */
    public function testSetMessage(): void
    {
        $this->object->setMessage('test&<>', false);
        self::assertSame('test&<>', $this->object->getMessage());
        $this->object->setMessage('test&<>', true);
        self::assertSame('test&amp;&lt;&gt;', $this->object->getMessage());
    }

    /**
     * testing setter of string
     */
    public function testSetString(): void
    {
        $this->object->setString('test&<>', false);
        self::assertSame('test&<>', $this->object->getString());
        $this->object->setString('test&<>', true);
        self::assertSame('test&amp;&lt;&gt;', $this->object->getString());
    }

    /**
     * testing add param method
     */
    public function testAddParam(): void
    {
        $this->object->addParam(Message::notice('test'));
        self::assertEquals([Message::notice('test')], $this->object->getParams());
        $this->object->addParam('test');
        self::assertEquals([
            Message::notice('test'),
            'test',
        ], $this->object->getParams());
        $this->object->addParam('test');
        self::assertEquals([
            Message::notice('test'),
            'test',
            Message::notice('test'),
        ], $this->object->getParams());
    }

    /**
     * Test adding html markup
     */
    public function testAddParamHtml(): void
    {
        $this->object->setMessage('Hello %s%s%s');
        $this->object->addParamHtml('<a href="">');
        $this->object->addParam('user<>');
        $this->object->addParamHtml('</a>');
        self::assertSame('Hello <a href="">user&lt;&gt;</a>', $this->object->getMessage());
    }

    /**
     * testing add string method
     */
    public function testAddString(): void
    {
        $this->object->addText('test', '*');
        self::assertEquals([
            '*',
            Message::notice('test'),
        ], $this->object->getAddedMessages());
        $this->object->addText('test', '');
        self::assertEquals([
            '*',
            Message::notice('test'),
            Message::notice('test'),
        ], $this->object->getAddedMessages());
    }

    /**
     * testing add message method
     */
    public function testAddMessage(): void
    {
        $this->object->addText('test<>', '');
        self::assertEquals([Message::notice('test&lt;&gt;')], $this->object->getAddedMessages());
        $this->object->addHtml('<b>test</b>');
        self::assertEquals([
            Message::notice('test&lt;&gt;'),
            ' ',
            Message::rawNotice('<b>test</b>'),
        ], $this->object->getAddedMessages());
        $this->object->addMessage(Message::notice('test<>'));
        self::assertSame('test&lt;&gt; <b>test</b> test<>', $this->object->getMessage());
    }

    /**
     * testing add messages method
     */
    public function testAddMessages(): void
    {
        $messages = [];
        $messages[] = new Message('Test1');
        $messages[] = new Message('PMA_Test2', Message::ERROR);
        $messages[] = new Message('Test3');
        $this->object->addMessages($messages, '');

        self::assertEquals([
            Message::notice('Test1'),
            Message::error('PMA_Test2'),
            Message::notice('Test3'),
        ], $this->object->getAddedMessages());
    }

    /**
     * testing add messages method
     */
    public function testAddMessagesString(): void
    {
        $messages = [
            'test1',
            'test<b>',
            'test2',
        ];
        $this->object->addMessagesString($messages, '');

        self::assertEquals([
            Message::notice('test1'),
            Message::notice('test&lt;b&gt;'),
            Message::notice('test2'),
        ], $this->object->getAddedMessages());

        self::assertSame('test1test&lt;b&gt;test2', $this->object->getMessage());
    }

    /**
     * testing setter of params
     */
    public function testSetParams(): void
    {
        $this->object->setParams(['test&<>']);
        self::assertSame(['test&<>'], $this->object->getParams());
        $this->object->setParams(['test&<>'], true);
        self::assertSame(['test&amp;&lt;&gt;'], $this->object->getParams());
    }

    /**
     * testing sanitize method
     */
    public function testSanitize(): void
    {
        $this->object->setString('test&string<>', false);
        self::assertSame('test&amp;string&lt;&gt;', Message::sanitize($this->object));
        self::assertSame([
            'test&amp;string&lt;&gt;',
            'test&amp;string&lt;&gt;',
        ], Message::sanitize([$this->object, $this->object]));
    }

    /**
     * Data provider for testDecodeBB
     *
     * @return array Test data
     */
    public static function decodeBBDataProvider(): array
    {
        return [
            [
                '[em]test[/em][em]aa[em/][em]test[/em]',
                '<em>test</em><em>aa[em/]<em>test</em>',
            ],
            [
                '[strong]test[/strong][strong]test[/strong]',
                '<strong>test</strong><strong>test</strong>',
            ],
            [
                '[code]test[/code][code]test[/code]',
                '<code>test</code><code>test</code>',
            ],
            [
                '[kbd]test[/kbd][br][sup]test[/sup]',
                '<kbd>test</kbd><br><sup>test</sup>',
            ],
            [
                '[a@https://example.com/@Documentation]link[/a]',
                '<a href="./url.php?url=https%3A%2F%2Fexample.com%2F" target="Documentation">link</a>',
            ],
            [
                '[a@./non-existing@Documentation]link[/a]',
                '[a@./non-existing@Documentation]link</a>',
            ],
            [
                '[doc@foo]link[/doc]',
                '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2F'
                . 'latest%2Fsetup.html%23foo" '
                . 'target="documentation">link</a>',
            ],
            [
                '[doc@page@anchor]link[/doc]',
                '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2F'
                . 'latest%2Fpage.html%23anchor" '
                . 'target="documentation">link</a>',
            ],
            [
                '[doc@faqmysql]link[/doc]',
                '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2F'
                . 'latest%2Ffaq.html%23faqmysql" '
                . 'target="documentation">link</a>',
            ],
        ];
    }

    /**
     * testing decodeBB method
     *
     * @param string $actual   BB code string
     * @param string $expected Expected decoded string
     *
     * @dataProvider decodeBBDataProvider
     */
    public function testDecodeBB(string $actual, string $expected): void
    {
        unset($GLOBALS['server']);
        self::assertSame($expected, Message::decodeBB($actual));
    }

    /**
     * testing format method
     */
    public function testFormat(): void
    {
        self::assertSame('test string', Message::format('test string'));
        self::assertSame('test string', Message::format('test string', 'a'));
        self::assertSame('test string', Message::format('test string', []));
        self::assertSame('test string', Message::format('%s string', ['test']));
    }

    /**
     * testing getHash method
     */
    public function testGetHash(): void
    {
        $this->object->setString('<&>test', false);
        $this->object->setMessage('<&>test', false);
        self::assertSame(md5(Message::NOTICE . '<&>test<&>test'), $this->object->getHash());
    }

    /**
     * getMessage test - with empty message and with non-empty string -
     * not key in globals additional params are defined
     */
    public function testGetMessageWithoutMessageWithStringWithParams(): void
    {
        $this->object->setMessage('');
        $this->object->setString('test string %s %s');
        $this->object->addParam('test param 1');
        $this->object->addParam('test param 2');
        self::assertSame('test string test param 1 test param 2', $this->object->getMessage());
    }

    /**
     * getMessage test - with empty message and with empty string
     */
    public function testGetMessageWithoutMessageWithEmptyString(): void
    {
        $this->object->setMessage('');
        $this->object->setString('');
        self::assertSame('', $this->object->getMessage());
    }

    /**
     * getMessage test - message is defined
     * message with BBCode defined
     */
    public function testGetMessageWithMessageWithBBCode(): void
    {
        $this->object->setMessage('[kbd]test[/kbd] [doc@cfg_Example]test[/doc]');
        self::assertSame('<kbd>test</kbd> <a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.'
        . 'net%2Fen%2Flatest%2Fconfig.html%23cfg_Example"'
        . ' target="documentation">test</a>', $this->object->getMessage());
    }

    /**
     * getLevel test
     */
    public function testGetLevel(): void
    {
        self::assertSame('notice', $this->object->getLevel());
        $this->object->setNumber(Message::SUCCESS);
        self::assertSame('success', $this->object->getLevel());
        $this->object->setNumber(Message::ERROR);
        self::assertSame('error', $this->object->getLevel());
    }

    /**
     * getDisplay test
     */
    public function testGetDisplay(): void
    {
        self::assertFalse($this->object->isDisplayed());
        $this->object->setMessage('Test Message');
        self::assertSame('<div class="alert alert-primary" role="alert">' . "\n"
        . '  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice"> Test Message' . "\n"
        . '</div>' . "\n", $this->object->getDisplay());
        self::assertTrue($this->object->isDisplayed());
    }

    /**
     * isDisplayed test
     */
    public function testIsDisplayed(): void
    {
        self::assertFalse($this->object->isDisplayed(false));
        self::assertTrue($this->object->isDisplayed(true));
        self::assertTrue($this->object->isDisplayed(false));
    }

    /**
     * Data provider for testAffectedRows
     *
     * @return array Test-data
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
     *
     * @dataProvider providerAffectedRows
     */
    public function testAffectedRows(int $rows, string $output): void
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForAffectedRows($rows);
        $this->object->addMessage($msg);
        self::assertSame($output, $this->object->getDisplay());
    }

    /**
     * Data provider for testInsertedRows
     *
     * @return array Test-data
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
     *
     * @dataProvider providerInsertedRows
     */
    public function testInsertedRows(int $rows, string $output): void
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForInsertedRows($rows);
        $this->object->addMessage($msg);
        self::assertSame($output, $this->object->getDisplay());
    }

    /**
     * Data provider for testDeletedRows
     *
     * @return array Test-data
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
     *
     * @dataProvider providerDeletedRows
     */
    public function testDeletedRows(int $rows, string $output): void
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForDeletedRows($rows);
        $this->object->addMessage($msg);
        self::assertSame($output, $this->object->getDisplay());
    }
}
