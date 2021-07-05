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

use const SORT_NATURAL;
use const SORT_REGULAR;

/**
 * @covers \PhpMyAdmin\Command\TwigLintCommand
 */
class TwigLintCommandTest extends AbstractTestCase
{
    /** @var TwigLintCommand */
    private $command;

    public function setUp(): void
    {
        global $cfg, $config;

        if (! class_exists(Command::class)) {
            $this->markTestSkipped('The Symfony Console is missing');
        }

        parent::loadContainerBuilder();
        $cfg['environment'] = 'development';
        $config = null;

        $this->command = new TwigLintCommand();
    }

    public function testGetTemplateContents(): void
    {
        $contents = $this->callFunction($this->command, TwigLintCommand::class, 'getTemplateContents', [
            ROOT_PATH . 'test/classes/_data/file_listing/subfolder/one.ini',
        ]);

        $this->assertSame('key=value' . "\n", $contents);
    }

    public function testFindFiles(): void
    {
        $filesFound = $this->callFunction($this->command, TwigLintCommand::class, 'findFiles', [
            ROOT_PATH . 'test/classes/_data/file_listing',
        ]);

        // Sort results to avoid file system test specific failures
        sort($filesFound, SORT_NATURAL);

        $this->assertEquals([
            ROOT_PATH . 'test/classes/_data/file_listing/one.txt',
            ROOT_PATH . 'test/classes/_data/file_listing/subfolder/one.ini',
            ROOT_PATH . 'test/classes/_data/file_listing/subfolder/zero.txt',
            ROOT_PATH . 'test/classes/_data/file_listing/two.md',
        ], $filesFound);
    }

    public function testGetFilesInfo(): void
    {
        $filesInfos = $this->callFunction($this->command, TwigLintCommand::class, 'getFilesInfo', [
            ROOT_PATH . 'test/classes/_data/file_listing',
        ]);

        // Sort results to avoid file system test specific failures
        sort($filesInfos, SORT_REGULAR);

        $this->assertEquals([
            [
                'template' => '',
                'file' => ROOT_PATH . 'test/classes/_data/file_listing/one.txt',
                'valid' => true,
            ],
            [
                'template' => '',
                'file' => ROOT_PATH . 'test/classes/_data/file_listing/two.md',
                'valid' => true,
            ],
            [
                'template' => '0000' . "\n",
                'file' => ROOT_PATH . 'test/classes/_data/file_listing/subfolder/zero.txt',
                'valid' => true,
            ],
            [
                'template' => 'key=value' . "\n",
                'file' => ROOT_PATH . 'test/classes/_data/file_listing/subfolder/one.ini',
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
                [
                    'foo.twig',
                    'foo-invalid.twig',
                ]
            );

        $command->expects($this->exactly(2))
            ->method('getTemplateContents')
            ->withConsecutive(
                ['foo.twig'],
                ['foo-invalid.twig']
            )
            ->willReturnOnConsecutiveCalls(
                '{{ file }}',
                '{{ file }'
            );

        $filesFound = $this->callFunction($command, TwigLintCommand::class, 'getFilesInfo', [
            ROOT_PATH . 'test/classes/_data/file_listing',
        ]);

        $this->assertEquals([
            [
                'template' => '{{ file }}',
                'file' => 'foo.twig',
                'valid' => true,
            ],
            [
                'template' => '{{ file }',
                'file' => 'foo-invalid.twig',
                'valid' => false,
                'line' => 1,
                'exception' => new SyntaxError('Unexpected "}".', 1, new Source(
                    '{{ file }',
                    'foo-invalid.twig'
                )),
            ],
        ], $filesFound);
    }

    public function testGetContext(): void
    {
        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', [
            '{{ file }',
            0,
        ]);

        $this->assertEquals([1 => '{{ file }'], $context);

        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', [
            '{{ file }',
            3,
        ]);

        $this->assertEquals([1 => '{{ file }'], $context);

        $context = $this->callFunction($this->command, TwigLintCommand::class, 'getContext', [
            '{{ file }',
            5,
        ]);

        $this->assertEquals([], $context);
    }
}
