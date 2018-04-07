<?php
/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\ZipExtension;
use ZipArchive;

/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */
class ZipExtensionTest extends PmaTestCase
{
    private $zipExtension;

    protected function setUp()
    {
        $this->zipExtension = new ZipExtension();
    }

    /**
     * Test for getContents
     *
     * @param string $file           path to zip file
     * @param string $specific_entry regular expression to match a file
     * @param mixed  $output         expected output
     *
     * @dataProvider provideTestGetContents
     * @return void
     */
    public function testGetContents($file, $specific_entry, $output)
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
    public function provideTestGetContents()
    {
        return array(
            array(
                './test/test_data/test.zip',
                null,
                array(
                    'error' => '',
                    'data' => 'TEST FILE' . "\n"
                )
            ),
            array(
                './test/test_data/test.zip',
                'test',
                array(
                    'error' => 'Error in ZIP archive: Could not find "test"',
                    'data' => ''
                )
            )
        );
    }

    /**
     * Test for findFile
     *
     * @param string $file        path to zip file
     * @param string $file_regexp regular expression for the file name to match
     * @param mixed  $output      expected output
     *
     * @dataProvider provideTestFindFile
     * @return void
     */
    public function testFindFile($file, $file_regexp, $output)
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
    public function provideTestFindFile()
    {
        return array(
            array(
                './test/test_data/test.zip',
                '/test/',
                'test.file'
            )
        );
    }

    /**
     * Test for getNumberOfFiles
     *
     * @return void
     */
    public function testGetNumberOfFiles()
    {
        $this->assertEquals(
            $this->zipExtension->getNumberOfFiles('./test/test_data/test.zip'),
            1
        );
    }

    /**
     * Test for extract
     *
     * @return void
     */
    public function testExtract()
    {
        $this->assertEquals(
            false,
            $this->zipExtension->extract(
                './test/test_data/test.zip', 'wrongName'
            )
        );
        $this->assertEquals(
            "TEST FILE\n",
            $this->zipExtension->extract(
                './test/test_data/test.zip', 'test.file'
            )
        );
    }

    /**
     * Helper function to get ZipArchive for content.
     *
     * @return ZipArchive
     */
    private function getZip($file)
    {
        $tmp = tempnam('./', 'zip-test');
        $handle = fopen($tmp, 'w');
        fwrite($handle, $file);
        fclose($handle);

        $zip = new ZipArchive;
        $this->assertTrue(
            $zip->open($tmp)
        );

        unlink($tmp);

        return $zip;
    }

    /**
     * Test for createFile
     *
     * @return void
     */
    public function testCreateSingleFile()
    {
        $file = $this->zipExtension->createFile("Test content", "test.txt");
        $this->assertNotEmpty($file);

        $zip = $this->getZip($file);

        $this->assertEquals(0, $zip->locateName('test.txt'));
    }

    /**
     * Test for createFile
     *
     * @return void
     */
    public function testCreateFailure()
    {
        $this->assertEquals(
            false,
            $this->zipExtension->createFile(
                "Content",
                array("name1.txt", "name2.txt")
            )
        );
    }

    /**
     * Test for createFile
     *
     * @return void
     */
    public function testCreateMultiFile()
    {
        $file = $this->zipExtension->createFile(
            array("Content", 'Content2'),
            array("name1.txt", "name2.txt")
        );
        $this->assertNotEmpty($file);
        $zip = $this->getZip($file);
        $this->assertEquals(0, $zip->locateName('name1.txt'));
        $this->assertEquals(1, $zip->locateName('name2.txt'));
    }
}
