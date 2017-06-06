<?php
/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\ZipExtension;

require_once 'test/PMATestCase.php';

/**
 * Tests zip extension usage.
 *
 * @package PhpMyAdmin-test
 */
class ZipExtensionTest extends PMATestCase
{
    /**
     * Test zip file content
     *
     * @param string $file           zip file
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
     * Test Find file in Zip Archive
     *
     * @param string $file_regexp regular expression for the file name to match
     * @param string $file        zip archive
     * @param mixed  $output      expected output
     *
     * @dataProvider provideTestFindFile
     * @return void
     */
    public function testFindFile($file_regexp, $file, $output)
    {
        $this->assertEquals(
            ZipExtension::findFile($file_regexp, $file),
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
                '/test/',
                './test/test_data/test.zip',
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
     * Test for ZipExtension::getError
     *
     * @param int   $code   error code
     * @param mixed $output expected output
     *
     * @dataProvider provideTestGetError
     * @return void
     */
    public function testGetError($code, $output)
    {
        $this->assertEquals(
            ZipExtension::getError($code),
            $output
        );
    }

    /**
     * Provider for testGetZipError
     *
     * @return array
     */
    public function provideTestGetError()
    {
        return array(
            array(
                1,
                'Multi-disk zip archives not supported'
            ),
            array(
                5,
                'Read error'
            ),
            array(
                7,
                'CRC error'
            ),
            array(
                19,
                'Not a zip archive'
            ),
            array(
                21,
                'Zip archive inconsistent'
            ),
            array(
                404,
                404
            )
        );
    }
}
