<?php
/**
 * Tests for StreamReader
 *
 * @package PhpMyAdmin-test
 */

class PMA_StreamReader_Test extends PHPUnit_Framework_TestCase
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
     *
     * @return void
     */
    public function testRead()
    {
        $this->assertFalse($this->object->read(4));
    }

    /**
     * Test for seekto
     *
     * @return void
     */
    public function testSeekto()
    {
        $this->assertFalse($this->object->seekto(1));
    }

    /**
     * Test for currentpos
     *
     * @return void
     */
    public function testCurrentpos()
    {
        $this->assertFalse($this->object->currentpos());
    }

    /**
     * Test for length
     *
     * @return void
     */
    public function testLength()
    {
        $this->assertFalse($this->object->length());
    }

}
