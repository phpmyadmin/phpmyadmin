<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Error;

use function preg_match;

use const DIRECTORY_SEPARATOR;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

/**
 * @covers \PhpMyAdmin\Error
 */
class ErrorTest extends AbstractTestCase
{
    /** @var Error */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new Error(2, 'Compile Error', 'error.txt', 15);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for setBacktrace
     */
    public function testSetBacktrace(): void
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
        self::assertSame($bt, $this->object->getBacktrace());
    }

    /**
     * Test for setLine
     */
    public function testSetLine(): void
    {
        $this->object->setLine(15);
        self::assertSame(15, $this->object->getLine());
    }

    /**
     * Test for setFile
     *
     * @param string $file     actual
     * @param string $expected expected
     *
     * @dataProvider filePathProvider
     */
    public function testSetFile(string $file, string $expected): void
    {
        $this->object->setFile($file);
        self::assertSame($expected, $this->object->getFile());
    }

    /**
     * Data provider for setFile
     *
     * @return array
     */
    public static function filePathProvider(): array
    {
        return [
            [
                './ChangeLog',
                '.' . DIRECTORY_SEPARATOR . 'ChangeLog',
            ],
            [
                __FILE__,
                '.' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR
                    . 'classes' . DIRECTORY_SEPARATOR . 'ErrorTest.php',
            ],
            [
                './NONEXISTING',
                'NONEXISTING',
            ],
        ];
    }

    /**
     * Test for getHash
     */
    public function testGetHash(): void
    {
        self::assertSame(1, preg_match('/^([a-z0-9]*)$/', $this->object->getHash()));
    }

    /**
     * Test for getBacktraceDisplay
     *
     * @requires PHPUnit < 10
     */
    public function testGetBacktraceDisplay(): void
    {
        self::assertStringContainsString(
            'PHPUnit\Framework\TestResult->run(<Class:PhpMyAdmin\Tests\ErrorTest>)<br>',
            $this->object->getBacktraceDisplay()
        );
    }

    /**
     * Test for getDisplay
     */
    public function testGetDisplay(): void
    {
        self::assertStringContainsString(
            '<div class="alert alert-danger" role="alert"><strong>Warning</strong>',
            $this->object->getDisplay()
        );
    }

    /** @dataProvider errorLevelProvider */
    public function testGetLevel(int $errorNumber, string $expected): void
    {
        self::assertSame($expected, (new Error($errorNumber, 'Error', 'error.txt', 15))->getLevel());
    }

    /** @return iterable<string, array{int, string}> */
    public static function errorLevelProvider(): iterable
    {
        yield 'internal error' => [0, 'error'];
        yield 'E_ERROR error' => [E_ERROR, 'error'];
        yield 'E_WARNING error' => [E_WARNING, 'error'];
        yield 'E_PARSE error' => [E_PARSE, 'error'];
        yield 'E_NOTICE notice' => [E_NOTICE, 'notice'];
        yield 'E_CORE_ERROR error' => [E_CORE_ERROR, 'error'];
        yield 'E_CORE_WARNING error' => [E_CORE_WARNING, 'error'];
        yield 'E_COMPILE_ERROR error' => [E_COMPILE_ERROR, 'error'];
        yield 'E_COMPILE_WARNING error' => [E_COMPILE_WARNING, 'error'];
        yield 'E_USER_ERROR error' => [E_USER_ERROR, 'error'];
        yield 'E_USER_WARNING error' => [E_USER_WARNING, 'error'];
        yield 'E_USER_NOTICE notice' => [E_USER_NOTICE, 'notice'];
        yield 'E_STRICT notice' => [@E_STRICT, 'notice'];
        yield 'E_DEPRECATED notice' => [E_DEPRECATED, 'notice'];
        yield 'E_USER_DEPRECATED notice' => [E_USER_DEPRECATED, 'notice'];
        yield 'E_RECOVERABLE_ERROR error' => [E_RECOVERABLE_ERROR, 'error'];
    }

    /** @dataProvider errorTypeProvider */
    public function testGetType(int $errorNumber, string $expected): void
    {
        self::assertSame($expected, (new Error($errorNumber, 'Error', 'error.txt', 15))->getType());
    }

    /** @return iterable<string, array{int, string}> */
    public static function errorTypeProvider(): iterable
    {
        yield 'internal error' => [0, 'Internal error'];
        yield 'E_ERROR error' => [E_ERROR, 'Error'];
        yield 'E_WARNING warning' => [E_WARNING, 'Warning'];
        yield 'E_PARSE error' => [E_PARSE, 'Parsing Error'];
        yield 'E_NOTICE notice' => [E_NOTICE, 'Notice'];
        yield 'E_CORE_ERROR error' => [E_CORE_ERROR, 'Core Error'];
        yield 'E_CORE_WARNING warning' => [E_CORE_WARNING, 'Core Warning'];
        yield 'E_COMPILE_ERROR error' => [E_COMPILE_ERROR, 'Compile Error'];
        yield 'E_COMPILE_WARNING warning' => [E_COMPILE_WARNING, 'Compile Warning'];
        yield 'E_USER_ERROR error' => [E_USER_ERROR, 'User Error'];
        yield 'E_USER_WARNING warning' => [E_USER_WARNING, 'User Warning'];
        yield 'E_USER_NOTICE notice' => [E_USER_NOTICE, 'User Notice'];
        yield 'E_STRICT notice' => [@E_STRICT, 'Runtime Notice'];
        yield 'E_DEPRECATED notice' => [E_DEPRECATED, 'Deprecation Notice'];
        yield 'E_USER_DEPRECATED notice' => [E_USER_DEPRECATED, 'Deprecation Notice'];
        yield 'E_RECOVERABLE_ERROR error' => [E_RECOVERABLE_ERROR, 'Catchable Fatal Error'];
    }

    /**
     * Test for getHtmlTitle
     */
    public function testGetHtmlTitle(): void
    {
        self::assertSame('Warning: Compile Error', $this->object->getHtmlTitle());
    }

    /**
     * Test for getTitle
     */
    public function testGetTitle(): void
    {
        self::assertSame('Warning: Compile Error', $this->object->getTitle());
    }

    /**
     * Test for getBacktrace
     */
    public function testGetBacktrace(): void
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
        self::assertCount(4, $this->object->getBacktrace());

        // case: first 2 frames
        self::assertCount(2, $this->object->getBacktrace(2));
    }
}
