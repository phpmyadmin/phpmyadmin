<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Scripts;
use PhpMyAdmin\Version;
use ReflectionProperty;

use function rawurlencode;

/**
 * @covers \PhpMyAdmin\Scripts
 */
class ScriptsTest extends AbstractTestCase
{
    /** @var Scripts */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new Scripts();
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
     * Test for getDisplay
     */
    public function testGetDisplay(): void
    {
        $this->object->addFile('common.js');

        $actual = $this->object->getDisplay();

        self::assertStringContainsString('src="js/dist/common.js?v=' . rawurlencode(Version::VERSION) . '"', $actual);
        self::assertStringContainsString('.add(\'common.js\', 1)', $actual);
        self::assertStringContainsString('AJAX.fireOnload(\'common.js\')', $actual);
    }

    /**
     * test for addCode
     */
    public function testAddCode(): void
    {
        $this->object->addCode('alert(\'CodeAdded\');');

        $actual = $this->object->getDisplay();

        self::assertStringContainsString('alert(\'CodeAdded\');', $actual);
    }

    /**
     * test for getFiles
     */
    public function testGetFiles(): void
    {
        // codemirror's onload event is excluded
        $this->object->addFile('vendor/codemirror/lib/codemirror.js');

        $this->object->addFile('common.js');
        self::assertSame([
            [
                'name' => 'vendor/codemirror/lib/codemirror.js',
                'fire' => 0,
            ],
            [
                'name' => 'common.js',
                'fire' => 1,
            ],
        ], $this->object->getFiles());
    }

    /**
     * test for addFile
     */
    public function testAddFile(): void
    {
        $reflection = new ReflectionProperty(Scripts::class, 'files');
        $reflection->setAccessible(true);

        // Assert empty _files property of
        // Scripts
        self::assertSame([], $reflection->getValue($this->object));

        // Add one script file
        $file = 'common.js';
        $hash = 'd7716810d825f4b55d18727c3ccb24e6';
        $_files = [
            $hash => [
                'has_onload' => 1,
                'filename' => 'common.js',
                'params' => [],
            ],
        ];
        $this->object->addFile($file);
        self::assertSame($_files, $reflection->getValue($this->object));
    }

    /**
     * test for addFiles
     */
    public function testAddFiles(): void
    {
        $reflection = new ReflectionProperty(Scripts::class, 'files');
        $reflection->setAccessible(true);

        $filenames = [
            'common.js',
            'sql.js',
            'common.js',
        ];
        $_files = [
            'd7716810d825f4b55d18727c3ccb24e6' => [
                'has_onload' => 1,
                'filename' => 'common.js',
                'params' => [],
            ],
            '347a57484fcd6ea6d8a125e6e1d31f78' => [
                'has_onload' => 1,
                'filename' => 'sql.js',
                'params' => [],
            ],
        ];
        $this->object->addFiles($filenames);
        self::assertSame($_files, $reflection->getValue($this->object));
    }
}
