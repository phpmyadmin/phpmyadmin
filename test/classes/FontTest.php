<?php
/**
 * Tests for PhpMyAdmin\Font class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Font;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Font class
 *
 * @package PhpMyAdmin-test
 */
class FontTest extends PmaTestCase
{
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
            Font::getStringWidth("", "arial", "10")
        );

        // empty string
        $this->assertEquals(
            3,
            Font::getStringWidth(" ", "arial", "10")
        );

        // string "a"
        $this->assertEquals(
            6,
            Font::getStringWidth("a", "arial", "10")
        );

        // string "aa"
        $this->assertEquals(
            12,
            Font::getStringWidth("aa", "arial", "10")
        );

        // string "i"
        $this->assertEquals(
            3,
            Font::getStringWidth("i", "arial", "10")
        );

        // string "f"
        $this->assertEquals(
            3,
            Font::getStringWidth("f", "arial", "10")
        );

        // string "t"
        $this->assertEquals(
            3,
            Font::getStringWidth("t", "arial", "10")
        );

        // string "if"
        $this->assertEquals(
            5,
            Font::getStringWidth("if", "arial", "10")
        );

        // string "it"
        $this->assertEquals(
            6,
            Font::getStringWidth("it", "arial", "10")
        );

        // string "r"
        $this->assertEquals(
            4,
            Font::getStringWidth("r", "arial", "10")
        );

        // string "1"
        $this->assertEquals(
            5,
            Font::getStringWidth("1", "arial", "10")
        );

        // string "c"
        $this->assertEquals(
            5,
            Font::getStringWidth("c", "arial", "10")
        );

        // string "F"
        $this->assertEquals(
            7,
            Font::getStringWidth("F", "arial", "10")
        );

        // string "A"
        $this->assertEquals(
            7,
            Font::getStringWidth("A", "arial", "10")
        );

        // string "w"
        $this->assertEquals(
            8,
            Font::getStringWidth("w", "arial", "10")
        );

        // string "G"
        $this->assertEquals(
            8,
            Font::getStringWidth("G", "arial", "10")
        );

        // string "m"
        $this->assertEquals(
            9,
            Font::getStringWidth("m", "arial", "10")
        );

        // string "W"
        $this->assertEquals(
            10,
            Font::getStringWidth("W", "arial", "10")
        );

        // string "$"
        $this->assertEquals(
            3,
            Font::getStringWidth("$", "arial", "10")
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
            Font::getStringWidth("phpMyAdmin", "arial", "10")
        );

        // string "phpMyAdmin", with No font
        $this->assertEquals(
            59,
            Font::getStringWidth("phpMyAdmin", "", "10")
        );

        // string "phpMyAdmin", with Times 10
        $this->assertEquals(
            55,
            Font::getStringWidth("phpMyAdmin", "times", "10")
        );

        // string "phpMyAdmin", with Broadway 10
        $this->assertEquals(
            73,
            Font::getStringWidth("phpMyAdmin", "broadway", "10")
        );

    }

    /**
     * Test getStringWidth with different font sizes.
     *
     * @return void
     */
    function testGetStringWidthSize()
    {
        // string "phpMyAdmin", with font size 0
        $this->assertEquals(
            0,
            Font::getStringWidth("phpMyAdmin", "arial", "0")
        );

        // string "phpMyAdmin", with Arial 10
        $this->assertEquals(
            59,
            Font::getStringWidth("phpMyAdmin", "arial", "10")
        );

        // string "phpMyAdmin", with Arial 11
        $this->assertEquals(
            65,
            Font::getStringWidth("phpMyAdmin", "arial", "11")
        );

        // string "phpMyAdmin", with Arial 20
        $this->assertEquals(
            118,
            Font::getStringWidth("phpMyAdmin", "arial", "20")
        );
    }

    /**
     * Test getStringWidth with a custom charList.
     *
     * @return void
     */
    function testGetStringWidthCharLists()
    {
        // string "a", with invalid charlist (= string)
        $this->assertEquals(
            6,
            Font::getStringWidth("a", "arial", "10", "list")
        );

        // string "a", with invalid charlist (= array without proper structure)
        $this->assertEquals(
            6,
            Font::getStringWidth("a", "arial", "10", array("list"))
        );

        // string "a", with invalid charlist (= array without proper structure :
        // modifier is missing
        $this->assertEquals(
            6,
            Font::getStringWidth(
                "a", "arial", "10",
                array(array("chars" => "a"))
            )
        );

        // string "a", with invalid charlist (= array without proper structure :
        // chars is missing
        $this->assertEquals(
            6,
            Font::getStringWidth(
                "a", "arial", "10",
                array(array("modifier" => 0.61))
            )
        );

        // string "a", with invalid charlist (= array without proper structure :
        // chars is not an array
        $this->assertEquals(
            6,
            Font::getStringWidth(
                "a", "arial", "10",
                array(array("chars" => "a", "modifier" => 0.61))
            )
        );

        // string "a", with valid charlist
        $this->assertEquals(
            7,
            Font::getStringWidth(
                "a", "arial", "10",
                array(array("chars" => array("a"), "modifier" => 0.61))
            )
        );
    }
}
