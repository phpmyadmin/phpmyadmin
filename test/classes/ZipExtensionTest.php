<?php
/**
 * Tests zip extension usage.
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ZipExtension;
use ZipArchive;
use function fclose;
use function fopen;
use function fwrite;
use function tempnam;
use function unlink;

/**
 * Tests zip extension usage.
 *
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
        $this->assertEquals(
            $this->zipExtension->getContents($file, $specific_entry),
            $output
        );
    }

    /**
     * Provider for testGetZipContents
     *
     * @return array
     */
    public function provideTestGetContents(): array
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
        $this->assertEquals(
            $this->zipExtension->findFile($file, $file_regexp),
            $output
        );
    }

    /**
     * Provider for testFindFileFromZipArchive
     *
     * @return array Test data
     */
    public function provideTestFindFile(): array
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
        $this->assertEquals(
            $this->zipExtension->getNumberOfFiles('./test/test_data/test.zip'),
            1
        );
    }

    /**
     * Test for extract
     */
    public function testExtract(): void
    {
        $this->assertFalse(
            $this->zipExtension->extract(
                './test/test_data/test.zip',
                'wrongName'
            )
        );
        $this->assertEquals(
            "TEST FILE\n",
            $this->zipExtension->extract(
                './test/test_data/test.zip',
                'test.file'
            )
        );
    }

    /**
     * Test for createFile
     */
    public function testCreateSingleFile(): void
    {
        $file = $this->zipExtension->createFile('Test content', 'test.txt');
        $this->assertNotEmpty($file);
        $this->assertIsString($file);

        $tmp = tempnam('./', 'zip-test');
        $this->assertNotFalse($tmp);
        $handle = fopen($tmp, 'w');
        $this->assertNotFalse($handle);
        fwrite($handle, $file);
        fclose($handle);

        $zip = new ZipArchive();
        $this->assertTrue(
            $zip->open($tmp)
        );

        $this->assertEquals(0, $zip->locateName('test.txt'));

        $zip->close();
        unlink($tmp);
    }

    /**
     * Test for createFile
     */
    public function testCreateFailure(): void
    {
        $this->assertFalse(
            $this->zipExtension->createFile(
                'Content',
                [
                    'name1.txt',
                    'name2.txt',
                ]
            )
        );
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
        $this->assertNotEmpty($file);
        $this->assertIsString($file);

        $tmp = tempnam('./', 'zip-test');
        $this->assertNotFalse($tmp);
        $handle = fopen($tmp, 'w');
        $this->assertNotFalse($handle);
        fwrite($handle, $file);
        fclose($handle);

        $zip = new ZipArchive();
        $this->assertTrue(
            $zip->open($tmp)
        );

        $this->assertEquals(0, $zip->locateName('name1.txt'));
        $this->assertEquals(1, $zip->locateName('name2.txt'));

        $zip->close();
        unlink($tmp);
    }
}
