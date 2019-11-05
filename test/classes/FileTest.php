<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for File class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\File;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for PhpMyAdmin\File class
 *
 * @package PhpMyAdmin-test
 */
class FileTest extends PmaTestCase
{
    /**
     * Setup function for test cases
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['charset_conversion'] = false;
    }

    /**
     * Test for File::getCompression
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testMIME($file, $mime): void
    {
        $arr = new File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * Test for File::getContent
     *
     * @param string $file file string
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testBinaryContent($file): void
    {
        $data = '0x' . bin2hex(file_get_contents($file));
        $file = new File($file);
        $this->assertEquals($data, $file->getContent());
    }

    /**
     * Test for File::read
     *
     * @param string $file file string
     *
     * @return void
     * @dataProvider compressedFiles
     * @requires extension bz2 1
     * @requires extension zip 1
     */
    public function testReadCompressed($file): void
    {
        $file = new File($file);
        $file->setDecompressContent(true);
        $file->open();
        $this->assertEquals("TEST FILE\n", $file->read(100));
        $file->close();
    }

    /**
     * Data provider for tests
     *
     * @return array Test data
     */
    public function compressedFiles()
    {
        return [
            [
                './test/test_data/test.gz',
                'application/gzip',
            ],
            [
                './test/test_data/test.bz2',
                'application/bzip2',
            ],
            [
                './test/test_data/test.zip',
                'application/zip',
            ],
        ];
    }
}
