<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Command;

use PhpMyAdmin\Command\TwigLintCommand;
use PhpMyAdmin\Tests\AbstractTestCase;
use Symfony\Component\Console\Command\Command;
use Twig\Error\SyntaxError;
use Twig\Source;

use function class_exists;
use function sort;

use const DIRECTORY_SEPARATOR;
use const SORT_NATURAL;
use const SORT_REGULAR;
use const TEST_PATH;

/** @covers \PhpMyAdmin\Command\TwigLintCommand */
class TwigLintCommandTest extends AbstractTestCase
{
    private TwigLintCommand $command;

    public function setUp(): void
    {
        if (! class_exists(Command::class)) {
            $this->markTestSkipped('The Symfony Console is missing');
        }

        parent::setUp();

        $GLOBALS['cfg']['environment'] = 'development';
        $GLOBALS['config'] = null;

        $this->command = new TwigLintCommand();
    }

    public function testGetTemplateContents(): void
    {
        $contents = $this->callFunction($this->command, TwigLintCommand::class, 'getTemplateContents', [
            TEST_PATH . 'test/classes/_data/file_listing/subfolder/one.ini',
        ]);

        $this->assertSame('key=value' . "\n", $contents);
    }

    public function testFindFiles(): void
    {
        $path = TEST_PATH . 'test/classes/_data/file_listing';
        $filesFound = $this->callFunction($this->command, TwigLintCommand::class, 'findFiles', [$path]);

        // Sort results to avoid file system test specific failures
        sort($filesFound, SORT_NATURAL);

        $this->assertEquals([
            $path . DIRECTORY_SEPARATOR . 'one.txt',
            $path . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'one.ini',
            $path . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'zero.txt',
            $path . DIRECTORY_SEPARATOR . 'two.md',
        ], $filesFound);
    }

    public function testGetFilesInfo(): void
    {
        $path = TEST_PATH . 'test/classes/_data/file_listing';
        $filesInfos = $this->callFunction($this->command, TwigLintCommand::class, 'getFilesInfo', [$path]);

        // Sort results to avoid file system test specific failures
        sort($filesInfos, SORT_REGULAR);

        $this->assertEquals([
            ['template' => '', 'file' => $path . DIRECTORY_SEPARATOR . 'one.txt', 'valid' => true],
            ['template' => '', 'file' => $path . DIRECTORY_SEPARATOR . 'two.md', 'valid' => true],
            [
                'template' => '0000' . "\n",
                'file' => $path . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'zero.txt',
                'valid' => true,
            ],
            [
                'template' => 'key=value' . "\n",
                'file' => $path . DIRECTORY_SEPARATOR . 'subfolder' . DIRECTORY_SEPARATOR . 'one.ini',
                'valid' => true,
            ],
        ], $filesInfos);
    }

    public function testGetFilesInfoInvalidFile(): void
    {
        $command = $this->getMockBuilder(TwigLintCommand::class)
            ->onlyMethods(['getTemplateContents', 'findFiles'])
            ->getMock();

        $command->expects($this->exactly(1))
            ->method('findFiles')
            ->willReturn(
                ['foo.twig', 'foo-invalid.twig'],
            );

        $command->expects($this->exactly(2))->method('getTemplateContents')->willReturnMap([
            ['foo.twig', '{{ file }}'],
            ['foo-invalid.twig', '{{ file }'],
        ]);

        $filesFound = $this->callFunction($command, TwigLintCommand::class, 'getFilesInfo', [
            TEST_PATH . 'test/classes/_data/file_listing',
        ]);

        $this->assertEquals([
            ['template' => '{{ file }}', 'file' => 'foo.twig', 'valid' => true],
            [
                'template' => '{{ file }',
                'file' => 'foo-invalid.twig',
                'valid' => false,
                'line' => 1,
                'exception' => new SyntaxError('Unexpected "}".', 1, new Source(
                    '{{ file }',
                    'foo-invalid.twig',
                )),
            ],
        ], $filesFound);
    }

    public function testGetContext(): void
    {
        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', ['{{ file }', 0]);

        $this->assertEquals([1 => '{{ file }'], $context);

        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', ['{{ file }', 3]);

        $this->assertEquals([1 => '{{ file }'], $context);

        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', ['{{ file }', 5]);

        $this->assertEquals([], $context);
    }
}
