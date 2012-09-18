<?php
/**
 * Tests for displaing results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/zip.lib.php';

class PMA_zip_test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new ZipFile();

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for setDoWrite
     */
    public function testSetDoWrite()
    {
        $this->object->setDoWrite();
        $this->assertTrue($this->object->doWrite);
    }

    /**
     * Test for unix2DosTime
     *
     * @param $unixtime
     * @param $output
     *
     * @dataProvider providerForTestUnix2DosTime
     */
    public function testUnix2DosTime($unixTime, $output)
    {
        $this->assertEquals(
            $this->object->unix2DosTime($unixTime),
            $output
        );
    }

    public function providerForTestUnix2DosTime()
    {
        return array(
            array(
                123456,
                2162688
            ),
            array(
                234232,
                2162688
            ),
        );
    }

    /**
     * Test for addFile
     */
    public function testAddFile()
    {
        $this->assertEquals(
            $this->object->addFile('This is test content for the file', 'Test file'),
            ''
        );
        $this->assertTrue(!empty($this->object->ctrl_dir));
    }

    /**
     * Test for file
     */
    public function testFile()
    {
        $file = $this->object->file();
        $this->assertTrue(
            !empty($file)
        );
    }
}
