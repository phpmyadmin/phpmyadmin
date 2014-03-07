<?php
/**
 * Tests for PMA_SVG class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/schema/Svg_Relation_Schema.class.php';

/**
 * Tests for PMA_SVG class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Svg_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_SVG();
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
     * Test getStringWidth with different characters.
     *
     * @return void
     */
    function testGetStringWidth()
    {
        // empty string
        $this->assertEquals(
            0,
            $this->object->getStringWidth("", "arial", "10")
        );

        // empty string
        $this->assertEquals(
            3,
            $this->object->getStringWidth(" ", "arial", "10")
        );

        // string "a"
        $this->assertEquals(
            6,
            $this->object->getStringWidth("a", "arial", "10")
        );

        // string "aa"
        $this->assertEquals(
            12,
            $this->object->getStringWidth("aa", "arial", "10")
        );

        // string "i"
        $this->assertEquals(
            3,
            $this->object->getStringWidth("i", "arial", "10")
        );

        // string "f"
        $this->assertEquals(
            3,
            $this->object->getStringWidth("f", "arial", "10")
        );

        // string "t"
        $this->assertEquals(
            3,
            $this->object->getStringWidth("t", "arial", "10")
        );

        // string "if"
        $this->assertEquals(
            5,
            $this->object->getStringWidth("if", "arial", "10")
        );

        // string "it"
        $this->assertEquals(
            6,
            $this->object->getStringWidth("it", "arial", "10")
        );

        // string "r"
        $this->assertEquals(
            4,
            $this->object->getStringWidth("r", "arial", "10")
        );

        // string "1"
        $this->assertEquals(
            5,
            $this->object->getStringWidth("1", "arial", "10")
        );

        // string "c"
        $this->assertEquals(
            5,
            $this->object->getStringWidth("c", "arial", "10")
        );

        // string "F"
        $this->assertEquals(
            7,
            $this->object->getStringWidth("F", "arial", "10")
        );

        // string "A"
        $this->assertEquals(
            7,
            $this->object->getStringWidth("A", "arial", "10")
        );

        // string "w"
        $this->assertEquals(
            8,
            $this->object->getStringWidth("w", "arial", "10")
        );

        // string "G"
        $this->assertEquals(
            8,
            $this->object->getStringWidth("G", "arial", "10")
        );

        // string "m"
        $this->assertEquals(
            9,
            $this->object->getStringWidth("m", "arial", "10")
        );

        // string "W"
        $this->assertEquals(
            10,
            $this->object->getStringWidth("W", "arial", "10")
        );

        // string "$"
        $this->assertEquals(
            3,
            $this->object->getStringWidth("$", "arial", "10")
        );
    }

    /**
     * Test getStringWidth with different fonts.
     *
     * @return void
     */
    function testGetStringWidthFont()
    {
        // string "phpMyAdmin", with Arial 10
        $this->assertEquals(
            59,
            $this->object->getStringWidth("phpMyAdmin", "arial", "10")
        );

        // string "phpMyAdmin", with No font
        $this->assertEquals(
            59,
            $this->object->getStringWidth("phpMyAdmin", "", "10")
        );

        // string "phpMyAdmin", with Times 10
        $this->assertEquals(
            55,
            $this->object->getStringWidth("phpMyAdmin", "times", "10")
        );

        // string "phpMyAdmin", with Broadway 10
        $this->assertEquals(
            73,
            $this->object->getStringWidth("phpMyAdmin", "broadway", "10")
        );

    }

    /**
     * Test getStringWidth with different fonts.
     *
     * @return void
     */
    function testGetStringWidthSize()
    {
        // string "phpMyAdmin", with font size 0
        $this->assertEquals(
            0,
            $this->object->getStringWidth("phpMyAdmin", "arial", "0")
        );

        // string "phpMyAdmin", with Arial 10
        $this->assertEquals(
            59,
            $this->object->getStringWidth("phpMyAdmin", "arial", "10")
        );

        // string "phpMyAdmin", with Arial 11
        $this->assertEquals(
            65,
            $this->object->getStringWidth("phpMyAdmin", "arial", "11")
        );

        // string "phpMyAdmin", with Arial 20
        $this->assertEquals(
            118,
            $this->object->getStringWidth("phpMyAdmin", "arial", "20")
        );
    }
}
