<?php
/**
 * Tests for displaying results
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/zip.lib.php';

/**
 * Tests for displaying results
 *
 * @package PhpMyAdmin-test
 */
class PMA_Zip_Test extends PHPUnit_Framework_TestCase
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
     *
     * @return void
     */
    public function testSetDoWrite()
    {
        $this->object->setDoWrite();
        $this->assertTrue($this->object->doWrite);
    }

    /**
     * Test for unix2DosTime
     *
     * @param int $unixTime UNIX timestamp
     * @param int $output   DOS timestamp
     *
     * @dataProvider providerForTestUnix2DosTime
     *
     * @return void
     */
    public function testUnix2DosTime($unixTime, $output)
    {
        $this->assertEquals(
            $this->object->unix2DosTime($unixTime),
            $output
        );
    }

    /**
     * Provider for testUnix2DosTime
     *
     * @return array
     */
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
     *
     * @return void
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
     *
     * @return void
     */
    public function testFile()
    {
        $file = $this->object->file();
        $this->assertTrue(
            !empty($file)
        );
    }
}
