<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_File class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/File.class.php';

/**
 * tests for PMA_File class
 *
 * @package PhpMyAdmin-test
 */
class PMA_File_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Setup function for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['cfg']['BZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = true;
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['charset_conversion'] = false;
    }

    /**
     * Test for PMA_File::getCompression
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testMIME($file, $mime)
    {
        $arr = new PMA_File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * Test for PMA_File::getContent
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testBinaryContent($file, $mime)
    {
        $data = '0x' . bin2hex(file_get_contents($file));
        $file = new PMA_File($file);
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
?>
