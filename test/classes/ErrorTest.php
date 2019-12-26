<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Error.php
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Error;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/**
 * Error class testing.
 *
 * @package PhpMyAdmin-test
 */
class ErrorTest extends PmaTestCase
{
    /**
     * @var Error
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
    protected function setUp(): void
    {
        $this->object = new Error(2, 'Compile Error', 'error.txt', 15);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Test for setBacktrace
     *
     * @return void
     */
    public function testSetBacktrace()
    {
        $bt = [
            [
                'file' => 'bt1',
                'line' => 2,
                'function' => 'bar',
                'args' => ['foo' => $this],
            ],
        ];
        $this->object->setBacktrace($bt);
        $bt[0]['args']['foo'] = '<Class:PhpMyAdmin\Tests\ErrorTest>';
        $this->assertEquals($bt, $this->object->getBacktrace());
    }

    /**
     * Test for setLine
     *
     * @return void
     */
    public function testSetLine()
    {
        $this->object->setLine(15);
        $this->assertEquals(15, $this->object->getLine());
    }

    /**
     * Test for setFile
     *
     * @param string $file     actual
     * @param string $expected expected
     *
     * @return void
     *
     * @dataProvider filePathProvider
     */
    public function testSetFile($file, $expected): void
    {
        $this->object->setFile($file);
        $this->assertEquals($expected, $this->object->getFile());
    }

    /**
     * Data provider for setFile
     *
     * @return array
     */
    public function filePathProvider()
    {
        return [
            [
                './ChangeLog',
                '.' . DIRECTORY_SEPARATOR . 'ChangeLog',
            ],
            [
                __FILE__,
                '.' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'ErrorTest.php',
            ],
            [
                './NONEXISTING',
                'NONEXISTING',
            ],
        ];
    }

    /**
     * Test for getHash
     *
     * @return void
     */
    public function testGetHash()
    {
        $this->assertEquals(
            1,
            preg_match('/^([a-z0-9]*)$/', $this->object->getHash())
        );
    }

    /**
     * Test for getBacktraceDisplay
     *
     * @return void
     */
    public function testGetBacktraceDisplay()
    {
        $this->assertStringContainsString(
            'PHPUnit\Framework\TestResult->run(<Class:PhpMyAdmin\Tests\ErrorTest>)<br>',
            $this->object->getBacktraceDisplay()
        );
    }

    /**
     * Test for getDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $this->assertStringContainsString(
            '<div class="error"><strong>Warning</strong>',
            $this->object->getDisplay()
        );
    }

    /**
     * Test for getHtmlTitle
     *
     * @return void
     */
    public function testGetHtmlTitle()
    {
        $this->assertEquals('Warning: Compile Error', $this->object->getHtmlTitle());
    }

    /**
     * Test for getTitle
     *
     * @return void
     */
    public function testGetTitle()
    {
        $this->assertEquals('Warning: Compile Error', $this->object->getTitle());
    }

    /**
     * Test for getBacktrace
     *
     * @return void
     */
    public function testGetBacktrace()
    {
        $bt = [
            [
                'file' => 'bt1',
                'line' => 2,
                'function' => 'bar',
                'args' => ['foo' => 1],
            ],
            [
                'file' => 'bt2',
                'line' => 2,
                'function' => 'bar',
                'args' => ['foo' => 2],
            ],
            [
                'file' => 'bt3',
                'line' => 2,
                'function' => 'bar',
                'args' => ['foo' => 3],
            ],
            [
                'file' => 'bt4',
                'line' => 2,
                'function' => 'bar',
                'args' => ['foo' => 4],
            ],
        ];

        $this->object->setBacktrace($bt);

        // case: full backtrace
        $this->assertCount(4, $this->object->getBacktrace());

        // case: first 2 frames
        $this->assertCount(2, $this->object->getBacktrace(2));
    }
}
