<?php
/**
 * Tests for StringReader
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/streams.php';

class PMA_StringReader_test extends PHPUnit_Framework_TestCase
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
        $this->object = new StringReader('sample string');

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
     * @param $bytes
     *
     * @dataProvider providerForTestRead
     */
    public function testRead($bytes, $output)
    {
        $this->assertEquals(
            $this->object->read($bytes),
            $output
        );
    }

    /**
     * Provider for testRead
     * @return array
     */
    public function providerForTestRead()
    {
        return array(
            array(
                4,
                'samp'
            ),
            array(
                6,
                'sample'
            ),
            array(
                9,
                'sample st'
            )
        );
    }

    /**
     * Test for seekto
     */
    public function testSeekto()
    {
        $this->assertEquals(
            $this->object->seekto(3),
            3
        );
    }

    /**
     * Test for currentpos
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
     */
    public function testLength()
    {
        $this->assertEquals(
            $this->object->length(),
            13
        );
    }
}
