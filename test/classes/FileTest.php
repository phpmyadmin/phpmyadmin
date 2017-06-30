<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\File class
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/PMATestCase.php';

/**
 * tests for PhpMyAdmin\File class
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
     * Test for PhpMyAdmin\File::getCompression
     *
     * @param string $file file string
     * @param string $mime expected mime
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testMIME($file, $mime)
    {
        $arr = new PhpMyAdmin\File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * Test for PhpMyAdmin\File::getContent
     *
     * @param string $file file string
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testBinaryContent($file)
    {
        $data = '0x' . bin2hex(file_get_contents($file));
        $file = new PhpMyAdmin\File($file);
        $this->assertEquals($data, $file->getContent());
    }

    /**
     * Test for PhpMyAdmin\File::read
     *
     * @param string $file file string
     *
     * @return void
     * @dataProvider compressedFiles
     */
    public function testReadCompressed($file)
    {
        $file = new PhpMyAdmin\File($file);
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
        return array(
            array('./test/test_data/test.gz', 'application/gzip'),
            array('./test/test_data/test.bz2', 'application/bzip2'),
            array('./test/test_data/test.zip', 'application/zip'),
        );
    }
}
