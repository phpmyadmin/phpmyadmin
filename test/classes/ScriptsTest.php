<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Script.php
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Scripts;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Tests for Script.php
 *
 * @package PhpMyAdmin-test
 */
class ScriptsTest extends PmaTestCase
{
    /**
     * @var Scripts
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
        $this->object = new Scripts();
        if (! defined('PMA_USR_BROWSER_AGENT')) {
            define('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        }
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
     * Test for getDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $this->object->addFile('common.js');

        $actual = $this->object->getDisplay();

        $this->assertStringContainsString(
            'src="js/common.js?v=' . PMA_VERSION . '"',
            $actual
        );
        $this->assertStringContainsString(
            '.add(\'common.js\', 1)',
            $actual
        );
        $this->assertStringContainsString(
            'AJAX.fireOnload(\'common.js\')',
            $actual
        );
    }

    /**
     * test for addCode
     *
     * @return void
     */
    public function testAddCode()
    {
        $this->object->addCode('alert(\'CodeAdded\');');

        $actual = $this->object->getDisplay();

        $this->assertStringContainsString(
            'alert(\'CodeAdded\');',
            $actual
        );
    }

    /**
     * test for getFiles
     *
     * @return void
     */
    public function testGetFiles()
    {
        // codemirror's onload event is blacklisted
        $this->object->addFile('vendor/codemirror/lib/codemirror.js');

        $this->object->addFile('common.js');
        $this->assertEquals(
            [
                [
                    'name' => 'vendor/codemirror/lib/codemirror.js',
                    'fire' => 0,
                ],
                [
                    'name' => 'common.js',
                    'fire' => 1,
                ],
            ],
            $this->object->getFiles()
        );
    }

    /**
     * test for addFile
     *
     * @return void
     */
    public function testAddFile()
    {
        $reflection = new ReflectionProperty(Scripts::class, '_files');
        $reflection->setAccessible(true);

        // Assert empty _files property of
        // Scripts
        $this->assertEquals([], $reflection->getValue($this->object));

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
        $this->assertEquals($_files, $reflection->getValue($this->object));
    }

    /**
     * test for addFiles
     *
     * @return void
     */
    public function testAddFiles()
    {
        $reflection = new ReflectionProperty(Scripts::class, '_files');
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
        $this->assertEquals($_files, $reflection->getValue($this->object));
    }
}
