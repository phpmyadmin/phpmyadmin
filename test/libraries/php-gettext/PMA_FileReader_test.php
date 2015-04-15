<?php
/**
 * Tests for FileReader
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/streams.php';

class PMA_FileReader_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new FileReader('./test/test_data/test.file');

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
     * Test for read
     *
     * @return void
     */
    public function testRead()
    {
        $this->assertEquals(
            $this->object->read(4),
            'TEST'
        );

        $this->assertEquals(
            $this->object->read(false),
            ''
        );
    }

    /**
     * Test for seekto
     *
     * @return void
     */
    public function testSeekto()
    {
        $this->assertEquals(
            $this->object->seekto(1),
            1
        );
    }

    /**
     * Test for currentpos
     *
     * @return void
     */
    public function testCurrentpos()
    {
        $this->assertEquals(
            $this->object->currentpos(),
            0
        );
    }

    /**
     * Test for length
     *
     * @return void
     */
    public function testLength()
    {
        $this->assertEquals(
            $this->object->length(),
            10
        );
    }

    /**
     * Test for close
     *
     * @return void
     */
    public function testClose()
    {
        $this->assertEquals(
            $this->object->close(),
            null
        );
    }

    /**
     * Test for non existing file
     *
     * @return void
     */
    public function testForNonExistingFile()
    {
        $file = new FileReader('./path/for/no/file.txt');
        // looking at the return value of a constructor is curious
        // but the constructor returns a value
        $this->assertFalse(
            $file->__construct('./path/for/no/file.txt')
        );
    }

    public function testForCachedFileReader()
    {
        $reader = new CachedFileReader('./test/test_data/test.file');
        $this->assertEquals(
            $reader->__construct('./test/test_data/test.file'),
            null
        );
        $this->assertFalse(
            $reader->__construct('./path/for/no/file.txt')
        );

    }
}
