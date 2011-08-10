
<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_File class
 *
 * @package phpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
require_once 'libraries/File.class.php';

class PMA_File_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['cfg']['Server']['only_db'] = array('single\\_db');
    }

    public function testGzip()
    {
        $arr = new PMA_File('./test/test_data/test.gz');
        $this->assertEquals('application/gzip', $arr->getCompression());
    }

    public function testZip()
    {
        $arr = new PMA_File('./test/test_data/test.zip');
        $this->assertEquals('application/zip', $arr->getCompression());
    }

    public function testBzip()
    {
        $arr = new PMA_File('./test/test_data/test.bz2');
        $this->assertEquals('application/bzip2', $arr->getCompression());
    }
}
?>
