<?php
/**
 * Tests for StreamReader
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/streams.php';

class PMA_StreamReader_test extends PHPUnit_Framework_TestCase
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
        $this->object = new StreamReader();

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
     */
    public function testRead()
    {
        $this->assertFalse($this->object->read(4));
    }

    /**
     * Test for seekto
     */
    public function testSeekto()
    {
        $this->assertFalse($this->object->seekto(1));
    }

    /**
     * Test for currentpos
     */
    public function testCurrentpos()
    {
        $this->assertFalse($this->object->currentpos());
    }

    /**
     * Test for length
     */
    public function testLength()
    {
        $this->assertFalse($this->object->length());
    }

}
