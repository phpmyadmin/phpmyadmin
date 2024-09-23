<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Error;

use PhpMyAdmin\Error\Error;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function preg_match;
use function str_replace;

use const DIRECTORY_SEPARATOR;

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

    /** @psalm-return non-empty-string[][] */
    public static function validFilePathsProvider(): array
    {
        return [
            ['./ChangeLog', '/ChangeLog'],
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

    /** @psalm-return non-empty-string[][] */
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
        self::assertSame(
            1,
            preg_match('/^([a-z0-9]*)$/', $this->object->getHash()),
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
