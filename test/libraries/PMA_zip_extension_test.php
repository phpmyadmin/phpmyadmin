<?php
/**
 * Tests for displaing results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/zip_extension.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_zip_extension_test extends PHPUnit_Framework_TestCase {

    /**
     * @param string $file           zip file
     * @param string $specific_entry regular expression to match a file
     * @param $output
     *
     * @dataProvider providerForTestGetZipContents
     */
    public function testGetZipContents($file, $specific_entry, $output){
        $this->assertEquals(
            PMA_getZipContents($file, $specific_entry),
            $output
        );
    }

    /**
     * Provider for testGetZipContents
     */
    public function providerForTestGetZipContents(){
        return array(
            array(
                './test/test_data/test.zip',
                null,
                array(
                    'error' => '',
                    'data' => 'TEST FILE'. "\n"
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
     * @param string $file_regexp regular expression for the file name to match
     * @param string $file        zip archive
     * @param $output
     *
     * @dataProvider providerForTestFindFileFromZipArchive
     */
    public function testFindFileFromZipArchive($file_regexp, $file, $output){
        $this->assertEquals(
            PMA_findFileFromZipArchive($file_regexp, $file),
            $output
        );
    }

    /**
     * Provider for testFindFileFromZipArchive
     */
    public function providerForTestFindFileFromZipArchive(){
        return array(
            array(
                '/test/',
                './test/test_data/test.zip',
                'test.file'
            )
        );
    }

    /**
     * Test for PMA_getNoOfFilesInZip
     */
    public function testGetNoOfFilesInZip(){
        $this->assertEquals(
            PMA_getNoOfFilesInZip('./test/test_data/test.zip'),
            1
        );
    }

    /**
     * Test for PMA_zipExtract
     */
    public function testZipExtract(){
        $this->assertEquals(
            PMA_zipExtract('./test/test_data/test.zip', './test/test_data/', 'wrongName'),
            true
        );
    }

    /**
     * Test for PMA_getZipError
     *
     * @param $code error code
     * @param $output
     *
     * @dataProvider providerForTestGetZipError
     */
    public function testGetZipError($code, $output){
        $this->assertEquals(
            PMA_getZipError($code),
            $output
        );
    }

    /**
     * Provider for testGetZipError
     */
    public function providerForTestGetZipError(){
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

?>
