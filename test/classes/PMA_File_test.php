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

class PMA_File_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['cfg']['BZipDump'] = true;
        $GLOBALS['cfg']['GZipDump'] = true;
        $GLOBALS['cfg']['ZipDump'] = true;
        $GLOBALS['charset_conversion'] = false;
    }

    /**
     * @dataProvider compressedFiles
     */
    public function testMIME($file, $mime)
    {
        $arr = new PMA_File($file);
        $this->assertEquals($mime, $arr->getCompression());
    }

    /**
     * @dataProvider compressedFiles
     */
    public function testContent($file, $mime)
    {
        $orig = file_get_contents('./test/test_data/test.file');
        $file = new PMA_File($file);
        $file->setDecompressContent(true);
        $this->assertTrue($file->open());
        if ($mime == 'application/zip') {
            $this->assertEquals($orig, $file->content_uncompressed);
        } else {
            $this->assertEquals($orig, $file->getNextChunk());
        }
    }

    /**
     * @dataProvider compressedFiles
     */
    public function testBinaryContent($file, $mime)
    {
        $data = '0x' . bin2hex(file_get_contents($file));
        $file = new PMA_File($file);
        $this->assertEquals($data, $file->getContent());
    }

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
