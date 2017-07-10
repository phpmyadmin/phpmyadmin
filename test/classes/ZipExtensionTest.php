<?php
/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\ZipExtension;

require_once 'test/PMATestCase.php';

/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */
class ZipExtensionTest extends PMATestCase
{
    /**
     * Test for ZipExtension::getContents
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
            ZipExtension::getContents($file, $specific_entry),
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
     * Test for ZipExtension::findFile
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
            ZipExtension::findFile($file, $file_regexp),
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
     * Test for ZipExtension::getNumberOfFiles
     *
     * @return void
     */
    public function testGetNumberOfFiles()
    {
        $this->assertEquals(
            ZipExtension::getNumberOfFiles('./test/test_data/test.zip'),
            1
        );
    }

    /**
     * Test for ZipExtension::extract
     *
     * @return void
     */
    public function testExtract()
    {
        $this->assertEquals(
            false,
            ZipExtension::extract(
                './test/test_data/test.zip', 'wrongName'
            )
        );
        $this->assertEquals(
            "TEST FILE\n",
            ZipExtension::extract(
                './test/test_data/test.zip', 'test.file'
            )
        );
    }

    /**
     * Test for ZipExtension::createFile
     *
     * @return void
     */
    public function testCreateFile()
    {
        $file = ZipExtension::createFile("Test content", "test.txt");
        $this->assertTrue(!empty($file));

        $this->assertEquals(
            false,
            ZipExtension::createFile(
                "Content",
                array("name1.txt", "name2.txt"))
        );
    }
}
