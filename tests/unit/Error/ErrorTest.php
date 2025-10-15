<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Error;

use PhpMyAdmin\Error\Error;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function str_replace;

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

#[CoversClass(Error::class)]
class ErrorTest extends AbstractTestCase
{
    protected Error $object;

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
        $bt = [['file' => 'bt1', 'line' => 2, 'function' => 'bar', 'args' => ['foo' => $this]]];
        $this->object->setBacktrace($bt);
        $bt[0]['args']['foo'] = '<Class:PhpMyAdmin\Tests\Error\ErrorTest>';
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

    /** @psalm-param non-empty-string $expected */
    #[DataProvider('validFilePathsProvider')]
    public function testSetFileWithValidFiles(string $file, string $expected): void
    {
        $this->object->setFile($file);
        $filePath = $this->object->getFile();
        self::assertStringStartsWith('.' . DIRECTORY_SEPARATOR, $filePath);
        /** @psalm-var non-empty-string $expected */
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);
        self::assertStringEndsWith($expected, $filePath);
    }

    /** @return non-empty-string[][] */
    public static function validFilePathsProvider(): array
    {
        return [
            ['./LICENSE', '/LICENSE'],
            [__FILE__, '/tests/unit/Error/ErrorTest.php'],
            [__DIR__ . '/ErrorTest.php', '/tests/unit/Error/ErrorTest.php'],
            [__DIR__ . '/../Error/ErrorTest.php', '/tests/unit/Error/ErrorTest.php'],
        ];
    }

    #[DataProvider('invalidFilePathsProvider')]
    public function testSetFileWithInvalidFiles(string $file, string $expected): void
    {
        $this->object->setFile($file);
        $filePath = $this->object->getFile();
        self::assertStringStartsNotWith('./', $filePath);
        self::assertSame($expected, $filePath);
    }

    /** @return non-empty-string[][] */
    public static function invalidFilePathsProvider(): array
    {
        return [
            ['./NONEXISTING', 'NONEXISTING'],
            [__FILE__ . '.invalid', 'ErrorTest.php.invalid'],
            [__DIR__ . '/NONEXISTING', 'NONEXISTING'],
            [__DIR__ . '/../Error/NONEXISTING', 'NONEXISTING'],
        ];
    }

    /**
     * Test for getHash
     */
    public function testGetHash(): void
    {
        self::assertMatchesRegularExpression(
            '/^([a-z0-9]*)$/',
            $this->object->getHash(),
        );
    }

    /**
     * Test for getBacktraceDisplay
     */
    public function testGetBacktraceDisplay(): void
    {
        self::assertStringContainsString(
            'PHPUnit\Framework\TestRunner->run(<Class:PhpMyAdmin\Tests\Error\ErrorTest>)',
            $this->object->getBacktraceDisplay(),
        );
    }

    /**
     * Test for getDisplay
     */
    public function testGetDisplay(): void
    {
        $actual = $this->object->getDisplay();
        self::assertStringStartsWith(
            '<div class="alert alert-danger" role="alert"><p><strong>Warning</strong> in error.txt#15</p>'
            . '<img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Compile Error'
            . '<p class="mt-3"><strong>Backtrace</strong></p><ol class="list-group"><li class="list-group-item">',
            $actual,
        );
        self::assertStringContainsString(
            'PHPUnit\Framework\TestRunner->run(<Class:PhpMyAdmin\Tests\Error\ErrorTest>)</li>'
            . '<li class="list-group-item">',
            $actual,
        );
        self::assertStringEndsWith('</li></ol></div>' . "\n", $actual);
    }

    #[DataProvider('errorLevelProvider')]
    public function testGetLevel(int $errorNumber, string $expected): void
    {
        self::assertSame($expected, (new Error($errorNumber, 'Error', 'error.txt', 15))->getContext());
    }

    /** @return iterable<string, array{int, string}> */
    public static function errorLevelProvider(): iterable
    {
        yield 'internal error' => [0, 'danger'];
        yield 'E_ERROR error' => [E_ERROR, 'danger'];
        yield 'E_WARNING error' => [E_WARNING, 'danger'];
        yield 'E_PARSE error' => [E_PARSE, 'danger'];
        yield 'E_NOTICE notice' => [E_NOTICE, 'primary'];
        yield 'E_CORE_ERROR error' => [E_CORE_ERROR, 'danger'];
        yield 'E_CORE_WARNING error' => [E_CORE_WARNING, 'danger'];
        yield 'E_COMPILE_ERROR error' => [E_COMPILE_ERROR, 'danger'];
        yield 'E_COMPILE_WARNING error' => [E_COMPILE_WARNING, 'danger'];
        yield 'E_USER_ERROR error' => [E_USER_ERROR, 'danger'];
        yield 'E_USER_WARNING error' => [E_USER_WARNING, 'danger'];
        yield 'E_USER_NOTICE notice' => [E_USER_NOTICE, 'primary'];
        yield 'E_STRICT notice' => [@E_STRICT, 'primary'];
        yield 'E_DEPRECATED notice' => [E_DEPRECATED, 'primary'];
        yield 'E_USER_DEPRECATED notice' => [E_USER_DEPRECATED, 'primary'];
        yield 'E_RECOVERABLE_ERROR error' => [E_RECOVERABLE_ERROR, 'danger'];
    }

    #[DataProvider('errorTypeProvider')]
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
            ['file' => 'bt1', 'line' => 2, 'function' => 'bar', 'args' => ['foo' => 1]],
            ['file' => 'bt2', 'line' => 2, 'function' => 'bar', 'args' => ['foo' => 2]],
            ['file' => 'bt3', 'line' => 2, 'function' => 'bar', 'args' => ['foo' => 3]],
            ['file' => 'bt4', 'line' => 2, 'function' => 'bar', 'args' => ['foo' => 4]],
        ];

        $this->object->setBacktrace($bt);

        // case: full backtrace
        self::assertCount(4, $this->object->getBacktrace());

        // case: first 2 frames
        self::assertCount(2, $this->object->getBacktrace(2));
    }
}
