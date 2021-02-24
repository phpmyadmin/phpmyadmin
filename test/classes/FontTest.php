<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Font;

class FontTest extends AbstractTestCase
{
    /** @var Font */
    private $font;

    /**
     * Sets up the fixture
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->font = new Font();
    }

    /**
     * Test getStringWidth with different characters.
     */
    public function testGetStringWidth(): void
    {
        // empty string
        $this->assertEquals(
            0,
            $this->font->getStringWidth('', 'arial', 10)
        );

        // empty string
        $this->assertEquals(
            3,
            $this->font->getStringWidth(' ', 'arial', 10)
        );

        // string "a"
        $this->assertEquals(
            6,
            $this->font->getStringWidth('a', 'arial', 10)
        );

        // string "aa"
        $this->assertEquals(
            12,
            $this->font->getStringWidth('aa', 'arial', 10)
        );

        // string "i"
        $this->assertEquals(
            3,
            $this->font->getStringWidth('i', 'arial', 10)
        );

        // string "f"
        $this->assertEquals(
            3,
            $this->font->getStringWidth('f', 'arial', 10)
        );

        // string "t"
        $this->assertEquals(
            3,
            $this->font->getStringWidth('t', 'arial', 10)
        );

        // string "if"
        $this->assertEquals(
            5,
            $this->font->getStringWidth('if', 'arial', 10)
        );

        // string "it"
        $this->assertEquals(
            6,
            $this->font->getStringWidth('it', 'arial', 10)
        );

        // string "r"
        $this->assertEquals(
            4,
            $this->font->getStringWidth('r', 'arial', 10)
        );

        // string "1"
        $this->assertEquals(
            5,
            $this->font->getStringWidth('1', 'arial', 10)
        );

        // string "c"
        $this->assertEquals(
            5,
            $this->font->getStringWidth('c', 'arial', 10)
        );

        // string "F"
        $this->assertEquals(
            7,
            $this->font->getStringWidth('F', 'arial', 10)
        );

        // string "A"
        $this->assertEquals(
            7,
            $this->font->getStringWidth('A', 'arial', 10)
        );

        // string "w"
        $this->assertEquals(
            8,
            $this->font->getStringWidth('w', 'arial', 10)
        );

        // string "G"
        $this->assertEquals(
            8,
            $this->font->getStringWidth('G', 'arial', 10)
        );

        // string "m"
        $this->assertEquals(
            9,
            $this->font->getStringWidth('m', 'arial', 10)
        );

        // string "W"
        $this->assertEquals(
            10,
            $this->font->getStringWidth('W', 'arial', 10)
        );

        // string "$"
        $this->assertEquals(
            3,
            $this->font->getStringWidth('$', 'arial', 10)
        );
    }

    /**
     * Test getStringWidth with different fonts.
     */
    public function testGetStringWidthFont(): void
    {
        // string "phpMyAdmin", with Arial 10
        $this->assertEquals(
            59,
            $this->font->getStringWidth('phpMyAdmin', 'arial', 10)
        );

        // string "phpMyAdmin", with No font
        $this->assertEquals(
            59,
            $this->font->getStringWidth('phpMyAdmin', '', 10)
        );

        // string "phpMyAdmin", with Times 10
        $this->assertEquals(
            55,
            $this->font->getStringWidth('phpMyAdmin', 'times', 10)
        );

        // string "phpMyAdmin", with Broadway 10
        $this->assertEquals(
            73,
            $this->font->getStringWidth('phpMyAdmin', 'broadway', 10)
        );
    }

    /**
     * Test getStringWidth with different font sizes.
     */
    public function testGetStringWidthSize(): void
    {
        // string "phpMyAdmin", with font size 0
        $this->assertEquals(
            0,
            $this->font->getStringWidth('phpMyAdmin', 'arial', 0)
        );

        // string "phpMyAdmin", with Arial 10
        $this->assertEquals(
            59,
            $this->font->getStringWidth('phpMyAdmin', 'arial', 10)
        );

        // string "phpMyAdmin", with Arial 11
        $this->assertEquals(
            65,
            $this->font->getStringWidth('phpMyAdmin', 'arial', 11)
        );

        // string "phpMyAdmin", with Arial 20
        $this->assertEquals(
            118,
            $this->font->getStringWidth('phpMyAdmin', 'arial', 20)
        );
    }

    /**
     * Test getStringWidth with a custom charList.
     */
    public function testGetStringWidthCharLists(): void
    {
        // string "a", with invalid charlist (= array without proper structure)
        $this->assertEquals(
            6,
            $this->font->getStringWidth('a', 'arial', 10, ['list'])
        );

        // string "a", with invalid charlist (= array without proper structure :
        // modifier is missing
        $this->assertEquals(
            6,
            $this->font->getStringWidth(
                'a',
                'arial',
                10,
                [['chars' => 'a']]
            )
        );

        // string "a", with invalid charlist (= array without proper structure :
        // chars is missing
        $this->assertEquals(
            6,
            $this->font->getStringWidth(
                'a',
                'arial',
                10,
                [['modifier' => 0.61]]
            )
        );

        // string "a", with invalid charlist (= array without proper structure :
        // chars is not an array
        $this->assertEquals(
            6,
            $this->font->getStringWidth(
                'a',
                'arial',
                10,
                [
                    [
                        'chars' => 'a',
                        'modifier' => 0.61,
                    ],
                ]
            )
        );

        // string "a", with valid charlist
        $this->assertEquals(
            7,
            $this->font->getStringWidth(
                'a',
                'arial',
                10,
                [
                    [
                        'chars' => ['a'],
                        'modifier' => 0.61,
                    ],
                ]
            )
        );
    }
}
