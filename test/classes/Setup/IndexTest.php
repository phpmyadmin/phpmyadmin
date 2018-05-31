<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for methods under PhpMyAdmin\Setup\Index
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Setup\Index as SetupIndex;
use PHPUnit\Framework\TestCase;

/**
 * tests for methods under PhpMyAdmin\Setup\Index
 *
 * @package PhpMyAdmin-test
 */
class IndexTest extends TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['ProxyUrl'] = '';
    }

    /**
     * Test for SetupIndex::messagesBegin()
     *
     * @return void
     */
    public function testPMAmessagesBegin()
    {
        $_SESSION['messages'] = [
            [
                ['foo'],
                ['bar']
            ]
        ];

        SetupIndex::messagesBegin();

        $this->assertEquals(
            [
                [
                    [
                        0 => 'foo',
                        'fresh' => false,
                        'active' => false
                    ],
                    [
                        0 => 'bar',
                        'fresh' => false,
                        'active' => false
                    ]
                ]
            ],
            $_SESSION['messages']
        );

        // case 2

        unset($_SESSION['messages']);
        SetupIndex::messagesBegin();
        $this->assertEquals(
            [
                'error' => [],
                'notice' => []
            ],
            $_SESSION['messages']
        );
    }

    /**
     * Test for SetupIndex::messagesSet
     *
     * @return void
     */
    public function testPMAmessagesSet()
    {
        SetupIndex::messagesSet('type', '123', 'testTitle', 'msg');

        $this->assertEquals(
            [
                'fresh' => true,
                'active' => true,
                'title' => 'testTitle',
                'message' => 'msg'
            ],
            $_SESSION['messages']['type']['123']
        );
    }

    /**
     * Test for SetupIndex::messagesEnd
     *
     * @return void
     */
    public function testPMAmessagesEnd()
    {
        $_SESSION['messages'] = [
            [
                ['msg' => 'foo', 'active' => false],
                ['msg' => 'bar', 'active' => true],
            ]
        ];

        SetupIndex::messagesEnd();

        $this->assertEquals(
            [
                [
                    '1' => [
                        'msg' => 'bar',
                        'active' => 1
                    ]
                ]
            ],
            $_SESSION['messages']
        );
    }

    /**
     * Test for SetupIndex::messagesShowHtml
     *
     * @return void
     */
    public function testPMAMessagesShowHTML()
    {
        $_SESSION['messages'] = [
            'type' => [
                ['title' => 'foo', 'message' => '123', 'fresh' => false],
                ['title' => 'bar', 'message' => '321', 'fresh' => true],
            ]
        ];

        ob_start();
        SetupIndex::messagesShowHtml();
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
