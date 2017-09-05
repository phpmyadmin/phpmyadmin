<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under setup/lib/index.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */

require_once 'setup/lib/index.lib.php';

/**
 * tests for methods under setup/lib/index.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_SetupIndex_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['ProxyUrl'] = '';
    }

    /**
     * Test for PMA_messagesBegin()
     *
     * @return void
     */
    public function testPMAmessagesBegin()
    {
        $_SESSION['messages'] = array(
            array(
                array('foo'),
                array('bar')
            )
        );

        PMA_messagesBegin();

        $this->assertEquals(
            array(
                array(
                    array(
                        0 => 'foo',
                        'fresh' => false,
                        'active' => false
                    ),
                    array(
                        0 => 'bar',
                        'fresh' => false,
                        'active' => false
                    )
                )
            ),
            $_SESSION['messages']
        );

        // case 2

        unset($_SESSION['messages']);
        PMA_messagesBegin();
        $this->assertEquals(
            array(
                'error' => array(),
                'notice' => array()
            ),
            $_SESSION['messages']
        );
    }

    /**
     * Test for PMA_messagesSet
     *
     * @return void
     */
    public function testPMAmessagesSet()
    {
        PMA_messagesSet('type', '123', 'testTitle', 'msg');

        $this->assertEquals(
            array(
                'fresh' => true,
                'active' => true,
                'title' => 'testTitle',
                'message' => 'msg'
            ),
            $_SESSION['messages']['type']['123']
        );
    }

    /**
     * Test for PMA_messagesEnd
     *
     * @return void
     */
    public function testPMAmessagesEnd()
    {
        $_SESSION['messages'] = array(
            array(
                array('msg' => 'foo', 'active' => false),
                array('msg' => 'bar', 'active' => true),
            )
        );

        PMA_messagesEnd();

        $this->assertEquals(
            array(
                array(
                    '1' => array(
                        'msg' => 'bar',
                        'active' => 1
                    )
                )
            ),
            $_SESSION['messages']
        );
    }

    /**
     * Test for PMA_messagesShowHtml
     *
     * @return void
     */
    public function testPMAMessagesShowHTML()
    {
        $_SESSION['messages'] = array(
            'type' => array(
                array('title' => 'foo', 'message' => '123', 'fresh' => false),
                array('title' => 'bar', 'message' => '321', 'fresh' => true),
            )
        );

        ob_start();
        PMA_messagesShowHtml();
        $result = ob_get_clean();

        $this->assertContains(
            '<div class="type hiddenmessage" id="0"><h4>foo</h4>123</div>',
            $result
        );

        $this->assertContains(
            '<div class="type" id="1"><h4>bar</h4>321</div>',
            $result
        );
    }
}
