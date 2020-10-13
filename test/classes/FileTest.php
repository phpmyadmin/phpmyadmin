<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\File;
use function bin2hex;
use function file_get_contents;

class FileTest extends AbstractTestCase
{
    /**
     * Setup function for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['charset_conversion'] = false;
    }

    /**
     * Test for File::getCompression
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @dataProvider compressedFiles
     */
    public function testMIME(string $file, string $mime): void
    {
        $arr = new File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * Test for File::getContent
     *
     * @param string $file file string
     *
     * @dataProvider compressedFiles
     */
    public function testBinaryContent(string $file): void
    {
        $data = '0x' . bin2hex((string) file_get_contents($file));
        $file = new File($file);
        $this->assertEquals($data, $file->getContent());
    }

    /**
     * Test for File::read
     *
     * @param string $file file string
     *
     * @dataProvider compressedFiles
     * @requires extension bz2 1
     * @requires extension zip
     */
    public function testReadCompressed(string $file): void
    {
        $file = new File($file);
        $file->setDecompressContent(true);
        $file->open();
        $this->assertEquals("TEST FILE\n", $file->read(100));
        $file->close();
    }

    public function compressedFiles(): array
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
