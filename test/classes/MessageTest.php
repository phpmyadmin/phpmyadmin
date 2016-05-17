<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for Message class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\Theme;

require_once 'libraries/sanitizing.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Test for Message class
 *
 * @package PhpMyAdmin-test
 */
class MessageTest extends PMATestCase
{
    /**
     * @var    PMA\libraries\Message
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
        $this->object = new PMA\libraries\Message;
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemeImage'] = 'theme/';
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
        $this->object = new PMA\libraries\Message('test<&>', PMA\libraries\Message::SUCCESS);
        $this->assertEquals($this->object, PMA\libraries\Message::success('test<&>'));
        $this->assertEquals(
            'Your SQL query has been executed successfully.',
            PMA\libraries\Message::success()->getString()
        );
    }

    /**
     * test error method
     *
     * @return void
     */
    public function testError()
    {
        $this->object = new PMA\libraries\Message('test<&>', PMA\libraries\Message::ERROR);
        $this->assertEquals($this->object, PMA\libraries\Message::error('test<&>'));
        $this->assertEquals('Error', PMA\libraries\Message::error()->getString());
    }

    /**
     * test notice method
     *
     * @return void
     */
    public function testNotice()
    {
        $this->object = new PMA\libraries\Message('test<&>', PMA\libraries\Message::NOTICE);
        $this->assertEquals($this->object, PMA\libraries\Message::notice('test<&>'));
    }

    /**
     * test rawError method
     *
     * @return void
     */
    public function testRawError()
    {
        $this->object = new PMA\libraries\Message('', PMA\libraries\Message::ERROR);
        $this->object->setMessage('test<&>');

        $this->assertEquals($this->object, PMA\libraries\Message::rawError('test<&>'));
    }

    /**
     * test rawNotice method
     *
     * @return void
     */
    public function testRawNotice()
    {
        $this->object = new PMA\libraries\Message('', PMA\libraries\Message::NOTICE);
        $this->object->setMessage('test<&>');

        $this->assertEquals($this->object, PMA\libraries\Message::rawNotice('test<&>'));
    }

    /**
     * test rawSuccess method
     *
     * @return void
     */
    public function testRawSuccess()
    {
        $this->object = new PMA\libraries\Message('', PMA\libraries\Message::SUCCESS);
        $this->object->setMessage('test<&>');

        $this->assertEquals($this->object, PMA\libraries\Message::rawSuccess('test<&>'));
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
        $this->object->addParam(PMA\libraries\Message::notice('test'));
        $this->assertEquals(
            array(PMA\libraries\Message::notice('test')),
            $this->object->getParams()
        );
        $this->object->addParam('test', true);
        $this->assertEquals(
            array(PMA\libraries\Message::notice('test'), 'test'),
            $this->object->getParams()
        );
        $this->object->addParam('test', false);
        $this->assertEquals(
            array(PMA\libraries\Message::notice('test'), 'test', PMA\libraries\Message::notice('test')),
            $this->object->getParams()
        );
    }

    /**
     * testing add string method
     *
     * @return void
     */
    public function testAddString()
    {
        $this->object->addString('test', '*');
        $this->assertEquals(
            array('*', PMA\libraries\Message::notice('test')),
            $this->object->getAddedMessages()
        );
        $this->object->addString('test', '');
        $this->assertEquals(
            array(
                '*',
                PMA\libraries\Message::notice('test'),
                '',
                PMA\libraries\Message::notice('test')
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
        $this->object->addMessage('test', '');
        $this->assertEquals(
            array(PMA\libraries\Message::rawNotice('test')),
            $this->object->getAddedMessages()
        );
        $this->object->addMessage('test');
        $this->assertEquals(
            array(
                PMA\libraries\Message::rawNotice('test'),
                ' ',
                PMA\libraries\Message::rawNotice('test')
            ),
            $this->object->getAddedMessages()
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
        $messages[] = "Test1";
        $messages[] = new PMA\libraries\Message("PMA_Test2", PMA\libraries\Message::ERROR);
        $messages[] = "Test3";
        $this->object->addMessages($messages, '');

        $this->assertEquals(
            array(
                PMA\libraries\Message::rawNotice('Test1'),
                PMA\libraries\Message::error("PMA_Test2"),
                PMA\libraries\Message::rawNotice('Test3')
            ),
            $this->object->getAddedMessages()
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
            PMA\libraries\Message::sanitize($this->object)
        );
        $this->assertEquals(
            array('test&amp;string&lt;&gt;', 'test&amp;string&lt;&gt;'),
            PMA\libraries\Message::sanitize(array($this->object, $this->object))
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
                '[a@http://foo.bar/@Documentation]link[/a]',
                '<a href="./url.php?url=http%3A%2F%2Ffoo.bar%2F"'
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
        unset($GLOBALS['collation_connection']);
        $this->assertEquals($expected, PMA\libraries\Message::decodeBB($actual));
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
            PMA\libraries\Message::format('test string')
        );
        $this->assertEquals(
            'test string',
            PMA\libraries\Message::format('test string', 'a')
        );
        $this->assertEquals(
            'test string',
            PMA\libraries\Message::format('test string', array())
        );
        $this->assertEquals(
            'test string',
            PMA\libraries\Message::format('%s string', array('test'))
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
            md5(PMA\libraries\Message::NOTICE . '<&>test<&>test'),
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
     * getMessage test - with empty message and with string, which is key to GLOBALS
     * additional messages are defined
     *
     * @return void
     */
    public function testGetMessageWithoutMessageWithGlobalStringWithAddMessages()
    {
        $GLOBALS['key'] = 'test message';
        $this->object->setMessage('');
        $this->object->setString('key');
        $this->object->addMessage('test message 2', ' - ');
        $this->object->addMessage('test message 3', '&');
        $this->assertEquals(
            'test message - test message 2&test message 3',
            $this->object->getMessage()
        );
        unset($GLOBALS['key']);
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
        $this->object->setNumber(PMA\libraries\Message::SUCCESS);
        $this->assertEquals('success', $this->object->getLevel());
        $this->object->setNumber(PMA\libraries\Message::ERROR);
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
            '<div class="notice"><img src="theme/s_notice.png" title="" alt="" /> '
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
            '<div class="notice"><img src="theme/s_notice.png" title="" alt="" /> '
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
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  1 row affected.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  2 rows affected.</div>'
            ),
            array(
                10000,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  10000 rows affected.</div>'
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
        $this->object = new PMA\libraries\Message();
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
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  1 row inserted.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  2 rows inserted.</div>'
            ),
            array(
                100000,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  100000 rows inserted.</div>'
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
        $this->object = new PMA\libraries\Message();
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
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  1 row deleted.</div>'
            ),
            array(
                2,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  2 rows deleted.</div>'
            ),
            array(
                500000,
                '<div class="notice"><img src="theme/s_notice.png" title="" alt="" '
                . '/>  500000 rows deleted.</div>'
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
        $this->object = new PMA\libraries\Message();
        $msg = $this->object->getMessageForDeletedRows($rows);
        echo $this->object->addMessage($msg);
        $this->expectOutputString($output);
        $this->object->display();
    }
}
