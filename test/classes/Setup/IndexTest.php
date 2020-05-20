<?php
/**
 * tests for methods under PhpMyAdmin\Setup\Index
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Setup\Index as SetupIndex;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * tests for methods under PhpMyAdmin\Setup\Index
 */
class IndexTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
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
                ['bar'],
            ],
        ];

        SetupIndex::messagesBegin();

        $this->assertEquals(
            [
                [
                    [
                        0 => 'foo',
                        'fresh' => false,
                        'active' => false,
                    ],
                    [
                        0 => 'bar',
                        'fresh' => false,
                        'active' => false,
                    ],
                ],
            ],
            $_SESSION['messages']
        );

        // case 2

        unset($_SESSION['messages']);
        SetupIndex::messagesBegin();
        $this->assertEquals(
            [
                'error' => [],
                'notice' => [],
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
                'message' => 'msg',
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
                [
                    'msg' => 'foo',
                    'active' => false,
                ],
                [
                    'msg' => 'bar',
                    'active' => true,
                ],
            ],
        ];

        SetupIndex::messagesEnd();

        $this->assertEquals(
            [
                [
                    '1' => [
                        'msg' => 'bar',
                        'active' => 1,
                    ],
                ],
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
                [
                    'title' => 'foo',
                    'message' => '123',
                    'fresh' => false,
                ],
                [
                    'title' => 'bar',
                    'message' => '321',
                    'fresh' => true,
                ],
            ],
        ];

        $expected = [
            [
                'id' => 0,
                'title' => 'foo',
                'type' => 'type',
                'message' => '123',
                'is_hidden' => true,
            ],
            [
                'id' => 1,
                'title' => 'bar',
                'type' => 'type',
                'message' => '321',
                'is_hidden' => false,
            ],
        ];

        $this->assertEquals($expected, SetupIndex::messagesShowHtml());
    }
}
