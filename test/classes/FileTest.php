<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA\libraries\File class
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/PMATestCase.php';

/**
 * tests for PMA\libraries\File class
 *
 * @package PhpMyAdmin-test
 */
class FileTest extends PMATestCase
{
    /**
     * Setup function for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['charset_conversion'] = false;
    }

    /**
     * Test for PMA\libraries\File::getCompression
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testMIME($file, $mime)
    {
        $arr = new PMA\libraries\File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * Test for PMA\libraries\File::getContent
     *
     * @param string $file file string
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testBinaryContent($file)
    {
        $data = '0x' . bin2hex(file_get_contents($file));
        $file = new PMA\libraries\File($file);
        $this->assertEquals($data, $file->getContent());
    }

    /**
     * Data provider for tests
     *
     * @return array Test data
     */
    public function compressedFiles()
    {
        return array(
            array('./test/test_data/test.gz', 'application/gzip'),
            array('./test/test_data/test.bz2', 'application/bzip2'),
            array('./test/test_data/test.zip', 'application/zip'),
            );
    }
}
