<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ZipExtension;
use ZipArchive;

use function file_put_contents;
use function tempnam;
use function unlink;

/**
 * @covers \PhpMyAdmin\ZipExtension
 * @requires extension zip
 */
class ZipExtensionTest extends AbstractTestCase
{
    /** @var ZipExtension */
    private $zipExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->zipExtension = new ZipExtension(new ZipArchive());
    }

    /**
     * Test for getContents
     *
     * @param string      $file           path to zip file
     * @param string|null $specific_entry regular expression to match a file
     * @param mixed       $output         expected output
     *
     * @dataProvider provideTestGetContents
     */
    public function testGetContents(string $file, ?string $specific_entry, $output): void
    {
        self::assertSame($this->zipExtension->getContents($file, $specific_entry), $output);
    }

    /**
     * Provider for testGetZipContents
     *
     * @return array
     */
    public static function provideTestGetContents(): array
    {
        return [
            'null as specific entry' => [
                './test/test_data/test.zip',
                null,
                [
                    'error' => '',
                    'data' => 'TEST FILE' . "\n",
                ],
            ],
            'an existent specific entry' => [
                './test/test_data/test.zip',
                '/test.file/',
                [
                    'error' => '',
                    'data' => 'TEST FILE' . "\n",
                ],
            ],
            'a nonexistent specific entry' => [
                './test/test_data/test.zip',
                '/foobar/',
                [
                    'error' => 'Error in ZIP archive: Could not find "/foobar/"',
                    'data' => '',
                ],
            ],
        ];
    }

    /**
     * Test for findFile
     *
     * @param string $file        path to zip file
     * @param string $file_regexp regular expression for the file name to match
     * @param mixed  $output      expected output
     *
     * @dataProvider provideTestFindFile
     */
    public function testFindFile(string $file, string $file_regexp, $output): void
    {
        self::assertSame($this->zipExtension->findFile($file, $file_regexp), $output);
    }

    /**
     * Provider for testFindFileFromZipArchive
     *
     * @return array Test data
     */
    public static function provideTestFindFile(): array
    {
        return [
            [
                './test/test_data/test.zip',
                '/test/',
                'test.file',
            ],
        ];
    }

    /**
     * Test for getNumberOfFiles
     */
    public function testGetNumberOfFiles(): void
    {
        self::assertSame($this->zipExtension->getNumberOfFiles('./test/test_data/test.zip'), 1);
    }

    /**
     * Test for extract
     */
    public function testExtract(): void
    {
        self::assertFalse($this->zipExtension->extract(
            './test/test_data/test.zip',
            'wrongName'
        ));
        self::assertSame("TEST FILE\n", $this->zipExtension->extract(
            './test/test_data/test.zip',
            'test.file'
        ));
    }

    /**
     * Test for createFile
     */
    public function testCreateSingleFile(): void
    {
        $file = $this->zipExtension->createFile('Test content', 'test.txt');
        self::assertNotEmpty($file);
        self::assertIsString($file);

        $tmp = tempnam('./', 'zip-test');
        self::assertNotFalse($tmp);
        self::assertNotFalse(file_put_contents($tmp, $file));

        $zip = new ZipArchive();
        self::assertTrue($zip->open($tmp));

        self::assertSame(0, $zip->locateName('test.txt'));

        $zip->close();
        unlink($tmp);
    }

    /**
     * Test for createFile
     */
    public function testCreateFailure(): void
    {
        self::assertFalse($this->zipExtension->createFile(
            'Content',
            [
                'name1.txt',
                'name2.txt',
            ]
        ));
    }

    /**
     * Test for createFile
     */
    public function testCreateMultiFile(): void
    {
        $file = $this->zipExtension->createFile(
            [
                'Content',
                'Content2',
            ],
            [
                'name1.txt',
                'name2.txt',
            ]
        );
        self::assertNotEmpty($file);
        self::assertIsString($file);

        $tmp = tempnam('./', 'zip-test');
        self::assertNotFalse($tmp);
        self::assertNotFalse(file_put_contents($tmp, $file));

        $zip = new ZipArchive();
        self::assertTrue($zip->open($tmp));

        self::assertSame(0, $zip->locateName('name1.txt'));
        self::assertSame(1, $zip->locateName('name2.txt'));

        $zip->close();
        unlink($tmp);
    }
}
