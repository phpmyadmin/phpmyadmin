<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for Message class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Message;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for Message class
 *
 * @package PhpMyAdmin-test
 */
class MessageTest extends PmaTestCase
{
    /**
     * @var    PhpMyAdmin\Message
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new Message;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
    }

    /**
     * to String casting test
     *
     * @return void
     */
    public function testToString()
    {
        $this->object->setMessage('test<&>', true);
        $this->assertEquals('test&lt;&amp;&gt;', (string)$this->object);
    }

    /**
     * test success method
     *
     * @return void
     */
    public function testSuccess()
    {
        $this->object = new Message('test<&>', Message::SUCCESS);
        $this->assertEquals($this->object, Message::success('test<&>'));
        $this->assertEquals(
            'Your SQL query has been executed successfully.',
            Message::success()->getString()
        );
    }

    /**
     * test error method
     *
     * @return void
     */
    public function testError()
    {
        $this->object = new Message('test<&>', Message::ERROR);
        $this->assertEquals($this->object, Message::error('test<&>'));
        $this->assertEquals('Error', Message::error()->getString());
    }

    /**
     * test notice method
     *
     * @return void
     */
    public function testNotice()
    {
        $this->object = new Message('test<&>', Message::NOTICE);
        $this->assertEquals($this->object, Message::notice('test<&>'));
    }

    /**
     * test rawError method
     *
     * @return void
     */
    public function testRawError()
    {
        $this->object = new Message('', Message::ERROR);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        $this->assertEquals($this->object, Message::rawError('test<&>'));
    }

    /**
     * test rawNotice method
     *
     * @return void
     */
    public function testRawNotice()
    {
        $this->object = new Message('', Message::NOTICE);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        $this->assertEquals($this->object, Message::rawNotice('test<&>'));
    }

    /**
     * test rawSuccess method
     *
     * @return void
     */
    public function testRawSuccess()
    {
        $this->object = new Message('', Message::SUCCESS);
        $this->object->setMessage('test<&>');
        $this->object->setBBCode(false);

        $this->assertEquals($this->object, Message::rawSuccess('test<&>'));
    }

    /**
     * testing isSuccess method
     *
     * @return void
     */
    public function testIsSuccess()
    {
        $this->assertFalse($this->object->isSuccess());
        $this->assertTrue($this->object->isSuccess(true));
    }

    /**
     * testing isNotice method
     *
     * @return void
     */
    public function testIsNotice()
    {
        $this->assertTrue($this->object->isNotice());
        $this->object->isError(true);
        $this->assertFalse($this->object->isNotice());
        $this->assertTrue($this->object->isNotice(true));
    }

    /**
     * testing isError method
     *
     * @return void
     */
    public function testIsError()
    {
        $this->assertFalse($this->object->isError());
        $this->assertTrue($this->object->isError(true));
    }

    /**
     * testing setter of message
     *
     * @return void
     */
    public function testSetMessage()
    {
        $this->object->setMessage('test&<>', false);
        $this->assertEquals('test&<>', $this->object->getMessage());
        $this->object->setMessage('test&<>', true);
        $this->assertEquals('test&amp;&lt;&gt;', $this->object->getMessage());
    }

    /**
     * testing setter of string
     *
     * @return void
     */
    public function testSetString()
    {
        $this->object->setString('test&<>', false);
        $this->assertEquals('test&<>', $this->object->getString());
        $this->object->setString('test&<>', true);
        $this->assertEquals('test&amp;&lt;&gt;', $this->object->getString());
    }

    /**
     * testing add param method
     *
     * @return void
     */
    public function testAddParam()
    {
        $this->object->addParam(Message::notice('test'));
        $this->assertEquals(
            array(Message::notice('test')),
            $this->object->getParams()
        );
        $this->object->addParam('test');
        $this->assertEquals(
            array(Message::notice('test'), 'test'),
            $this->object->getParams()
        );
        $this->object->addParam('test');
        $this->assertEquals(
            array(Message::notice('test'), 'test', Message::notice('test')),
            $this->object->getParams()
        );
    }

    /**
     * Test adding html markup
     *
     * @return void
     */
    public function testAddParamHtml()
    {
        $this->object->setMessage('Hello %s%s%s');
        $this->object->addParamHtml('<a href="">');
        $this->object->addParam('user<>');
        $this->object->addParamHtml('</a>');
        $this->assertEquals(
            'Hello <a href="">user&lt;&gt;</a>',
            $this->object->getMessage()
        );
    }

    /**
     * testing add string method
     *
     * @return void
     */
    public function testAddString()
    {
        $this->object->addText('test', '*');
        $this->assertEquals(
            array('*', Message::notice('test')),
            $this->object->getAddedMessages()
        );
        $this->object->addText('test', '');
        $this->assertEquals(
            array(
                '*',
                Message::notice('test'),
                Message::notice('test')
            ),
            $this->object->getAddedMessages()
        );
    }

    /**
     * testing add message method
     *
     * @return void
     */
    public function testAddMessage()
    {
        $this->object->addText('test<>', '');
        $this->assertEquals(
            array(Message::notice('test&lt;&gt;')),
            $this->object->getAddedMessages()
        );
        $this->object->addHtml('<b>test</b>');
        $this->assertEquals(
            array(
                Message::notice('test&lt;&gt;'),
                ' ',
                Message::rawNotice('<b>test</b>')
            ),
            $this->object->getAddedMessages()
        );
        $this->object->addMessage(Message::notice('test<>'));
        $this->assertEquals(
            'test&lt;&gt; <b>test</b> test<>',
            $this->object->getMessage()
        );
    }

    /**
     * testing add messages method
     *
     * @return void
     */
    public function testAddMessages()
    {
        $messages = array();
        $messages[] = new Message("Test1");
        $messages[] = new Message("PMA_Test2", Message::ERROR);
        $messages[] = new Message("Test3");
        $this->object->addMessages($messages, '');

        $this->assertEquals(
            array(
                Message::notice('Test1'),
                Message::error("PMA_Test2"),
                Message::notice('Test3')
            ),
            $this->object->getAddedMessages()
        );
    }

    /**
     * testing add messages method
     *
     * @return void
     */
    public function testAddMessagesString()
    {
        $messages = array('test1', 'test<b>', 'test2');
        $this->object->addMessagesString($messages, '');

        $this->assertEquals(
            array(
                Message::notice('test1'),
                Message::notice('test&lt;b&gt;'),
                Message::notice('test2')
            ),
            $this->object->getAddedMessages()
        );

        $this->assertEquals(
            'test1test&lt;b&gt;test2',
            $this->object->getMessage()
        );
    }

    /**
     * testing setter of params
     *
     * @return void
     */
    public function testSetParams()
    {
        $this->object->setParams('test&<>');
        $this->assertEquals('test&<>', $this->object->getParams());
        $this->object->setParams('test&<>', true);
        $this->assertEquals('test&amp;&lt;&gt;', $this->object->getParams());
    }

    /**
     * testing sanitize method
     *
     * @return void
     */
    public function testSanitize()
    {
        $this->object->setString('test&string<>', false);
        $this->assertEquals(
            'test&amp;string&lt;&gt;',
            Message::sanitize($this->object)
        );
        $this->assertEquals(
            array('test&amp;string&lt;&gt;', 'test&amp;string&lt;&gt;'),
            Message::sanitize(array($this->object, $this->object))
        );
    }

    /**
     * Data provider for testDecodeBB
     *
     * @return array Test data
     */
    public function decodeBBDataProvider()
    {
        return array(
            array(
                '[em]test[/em][em]aa[em/][em]test[/em]',
                '<em>test</em><em>aa[em/]<em>test</em>'
            ),
            array(
                '[strong]test[/strong][strong]test[/strong]',
                '<strong>test</strong><strong>test</strong>'
            ),
            array(
                '[code]test[/code][code]test[/code]',
                '<code>test</code><code>test</code>'
            ),
            array(
                '[kbd]test[/kbd][br][sup]test[/sup]',
                '<kbd>test</kbd><br /><sup>test</sup>'
            ),
            array(
                '[a@https://example.com/@Documentation]link[/a]',
                '<a href="./url.php?url=https%3A%2F%2Fexample.com%2F"'
                . ' target="Documentation">link</a>'
            ),
            array(
                '[a@./non-existing@Documentation]link[/a]',
                '[a@./non-existing@Documentation]link</a>'
            ),
            array(
                '[doc@foo]link[/doc]',
                '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2F'
                . 'latest%2Fsetup.html%23foo" '
                . 'target="documentation">link</a>'
            ),
        );
    }

    /**
     * testing decodeBB method
     *
     * @param string $actual   BB code string
     * @param string $expected Expected decoded string
     *
     * @return void
     *
     * @dataProvider decodeBBDataProvider
     */
    public function testDecodeBB($actual, $expected)
    {
        unset($GLOBALS['server']);
        $this->assertEquals($expected, Message::decodeBB($actual));
    }

    /**
     * testing format method
     *
     * @return void
     */
    public function testFormat()
    {
        $this->assertEquals(
            'test string',
            Message::format('test string')
        );
        $this->assertEquals(
            'test string',
            Message::format('test string', 'a')
        );
        $this->assertEquals(
            'test string',
            Message::format('test string', array())
        );
        $this->assertEquals(
            'test string',
            Message::format('%s string', array('test'))
        );

    }

    /**
     * testing getHash method
     *
     * @return void
     */
    public function testGetHash()
    {
        $this->object->setString('<&>test', false);
        $this->object->setMessage('<&>test', false);
        $this->assertEquals(
            md5(Message::NOTICE . '<&>test<&>test'),
            $this->object->getHash()
        );
    }

    /**
     * getMessage test - with empty message and with non-empty string -
     * not key in globals additional params are defined
     *
     * @return void
     */
    public function testGetMessageWithoutMessageWithStringWithParams()
    {
        $this->object->setMessage('');
        $this->object->setString('test string %s %s');
        $this->object->addParam('test param 1');
        $this->object->addParam('test param 2');
        $this->assertEquals(
            'test string test param 1 test param 2',
            $this->object->getMessage()
        );
    }

    /**
     * getMessage test - with empty message and with empty string
     *
     * @return void
     */
    public function testGetMessageWithoutMessageWithEmptyString()
    {
        $this->object->setMessage('');
        $this->object->setString('');
        $this->assertEquals('', $this->object->getMessage());
    }

    /**
     * getMessage test - message is defined
     * message with BBCode defined
     *
     * @return void
     */
    public function testGetMessageWithMessageWithBBCode()
    {
        $this->object->setMessage('[kbd]test[/kbd] [doc@cfg_Example]test[/doc]');
        $this->assertEquals(
            '<kbd>test</kbd> <a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.'
            . 'net%2Fen%2Flatest%2Fconfig.html%23cfg_Example"'
            . ' target="documentation">test</a>',
            $this->object->getMessage()
        );
    }

    /**
     * getLevel test
     *
     * @return void
     */
    public function testGetLevel()
    {
        $this->assertEquals('notice', $this->object->getLevel());
        $this->object->setNumber(Message::SUCCESS);
        $this->assertEquals('success', $this->object->getLevel());
        $this->object->setNumber(Message::ERROR);
        $this->assertEquals('error', $this->object->getLevel());
    }

    /**
     * testing display method (output string and _is_displayed variable)
     *
     * @return void
     */
    public function testDisplay()
    {
        $this->assertFalse($this->object->isDisplayed());
        $this->object->setMessage('Test Message');

        $this->expectOutputString(
            '<div class="notice"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice" /> '
            . 'Test Message</div>'
        );
        $this->object->display();

        $this->assertTrue($this->object->isDisplayed());
    }

    /**
     * getDisplay test
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $this->object->setMessage('Test Message');
        $this->assertEquals(
            '<div class="notice"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_notice" /> '
            . 'Test Message</div>',
            $this->object->getDisplay()
        );
    }

    /**
     * isDisplayed test
     *
     * @return void
     */
    public function testIsDisplayed()
    {
        $this->assertFalse($this->object->isDisplayed(false));
        $this->assertTrue($this->object->isDisplayed(true));
        $this->assertTrue($this->object->isDisplayed(false));
    }

    /**
     * Data provider for testAffectedRows
     *
     * @return array Test-data
     */
    public function providerAffectedRows()
    {
        return array(
            array(
                1,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  1 row affected.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  2 rows affected.</div>'
            ),
            array(
                10000,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  10000 rows affected.</div>'
            )
        );
    }

    /**
     * Test for getMessageForAffectedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     *
     * @return void
     *
     * @dataProvider providerAffectedRows
     */
    public function testAffectedRows($rows, $output)
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForAffectedRows($rows);
        echo $this->object->addMessage($msg);
        $this->expectOutputString($output);
        $this->object->display();
    }

    /**
     * Data provider for testInsertedRows
     *
     * @return array Test-data
     */
    public function providerInsertedRows()
    {
        return array(
            array(
                1,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  1 row inserted.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  2 rows inserted.</div>'
            ),
            array(
                100000,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  100000 rows inserted.</div>'
            )
        );
    }

    /**
     * Test for getMessageForInsertedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     *
     * @return void
     *
     * @dataProvider providerInsertedRows
     */
    public function testInsertedRows($rows, $output)
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForInsertedRows($rows);
        echo $this->object->addMessage($msg);
        $this->expectOutputString($output);
        $this->object->display();
    }

    /**
     * Data provider for testDeletedRows
     *
     * @return array Test-data
     */
    public function providerDeletedRows()
    {
        return array(
            array(
                1,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  1 row deleted.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  2 rows deleted.</div>'
            ),
            array(
                500000,
                '<div class="notice"><img src="themes/dot.gif" title="" alt="" '
                . 'class="icon ic_s_notice" />  500000 rows deleted.</div>'
            )
        );
    }

    /**
     * Test for getMessageForDeletedRows() method
     *
     * @param int    $rows   Number of rows
     * @param string $output Expected string
     *
     * @return void
     *
     * @dataProvider providerDeletedRows
     */
    public function testDeletedRows($rows, $output)
    {
        $this->object = new Message();
        $msg = $this->object->getMessageForDeletedRows($rows);
        echo $this->object->addMessage($msg);
        $this->expectOutputString($output);
        $this->object->display();
    }
}
