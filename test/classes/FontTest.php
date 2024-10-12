<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Font;

/**
 * @covers \PhpMyAdmin\Font
 */
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
        self::assertEquals(0, $this->font->getStringWidth('', 'arial', 10));

        // empty string
        self::assertEquals(3, $this->font->getStringWidth(' ', 'arial', 10));

        // string "a"
        self::assertEquals(6, $this->font->getStringWidth('a', 'arial', 10));

        // string "aa"
        self::assertEquals(12, $this->font->getStringWidth('aa', 'arial', 10));

        // string "i"
        self::assertEquals(3, $this->font->getStringWidth('i', 'arial', 10));

        // string "f"
        self::assertEquals(3, $this->font->getStringWidth('f', 'arial', 10));

        // string "t"
        self::assertEquals(3, $this->font->getStringWidth('t', 'arial', 10));

        // string "if"
        self::assertEquals(5, $this->font->getStringWidth('if', 'arial', 10));

        // string "it"
        self::assertEquals(6, $this->font->getStringWidth('it', 'arial', 10));

        // string "r"
        self::assertEquals(4, $this->font->getStringWidth('r', 'arial', 10));

        // string "1"
        self::assertEquals(5, $this->font->getStringWidth('1', 'arial', 10));

        // string "c"
        self::assertEquals(5, $this->font->getStringWidth('c', 'arial', 10));

        // string "F"
        self::assertEquals(7, $this->font->getStringWidth('F', 'arial', 10));

        // string "A"
        self::assertEquals(7, $this->font->getStringWidth('A', 'arial', 10));

        // string "w"
        self::assertEquals(8, $this->font->getStringWidth('w', 'arial', 10));

        // string "G"
        self::assertEquals(8, $this->font->getStringWidth('G', 'arial', 10));

        // string "m"
        self::assertEquals(9, $this->font->getStringWidth('m', 'arial', 10));

        // string "W"
        self::assertEquals(10, $this->font->getStringWidth('W', 'arial', 10));

        // string "$"
        self::assertEquals(3, $this->font->getStringWidth('$', 'arial', 10));
    }

    /**
     * Test getStringWidth with different fonts.
     */
    public function testGetStringWidthFont(): void
    {
        // string "phpMyAdmin", with Arial 10
        self::assertEquals(59, $this->font->getStringWidth('phpMyAdmin', 'arial', 10));

        // string "phpMyAdmin", with No font
        self::assertEquals(59, $this->font->getStringWidth('phpMyAdmin', '', 10));

        // string "phpMyAdmin", with Times 10
        self::assertEquals(55, $this->font->getStringWidth('phpMyAdmin', 'times', 10));

        // string "phpMyAdmin", with Broadway 10
        self::assertEquals(73, $this->font->getStringWidth('phpMyAdmin', 'broadway', 10));
    }

    /**
     * Test getStringWidth with different font sizes.
     */
    public function testGetStringWidthSize(): void
    {
        // string "phpMyAdmin", with font size 0
        self::assertEquals(0, $this->font->getStringWidth('phpMyAdmin', 'arial', 0));

        // string "phpMyAdmin", with Arial 10
        self::assertEquals(59, $this->font->getStringWidth('phpMyAdmin', 'arial', 10));

        // string "phpMyAdmin", with Arial 11
        self::assertEquals(65, $this->font->getStringWidth('phpMyAdmin', 'arial', 11));

        // string "phpMyAdmin", with Arial 20
        self::assertEquals(118, $this->font->getStringWidth('phpMyAdmin', 'arial', 20));
    }

    /**
     * Test getStringWidth with a custom charList.
     */
    public function testGetStringWidthCharLists(): void
    {
        // string "a", with invalid charlist (= array without proper structure)
        self::assertEquals(6, $this->font->getStringWidth('a', 'arial', 10, ['list']));

        // string "a", with invalid charlist (= array without proper structure :
        // modifier is missing
        self::assertEquals(6, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [['chars' => 'a']]
        ));

        // string "a", with invalid charlist (= array without proper structure :
        // chars is missing
        self::assertEquals(6, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [['modifier' => 0.61]]
        ));

        // string "a", with invalid charlist (= array without proper structure :
        // chars is not an array
        self::assertEquals(6, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [
                [
                    'chars' => 'a',
                    'modifier' => 0.61,
                ],
            ]
        ));

        // string "a", with valid charlist
        self::assertEquals(7, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [
                [
                    'chars' => ['a'],
                    'modifier' => 0.61,
                ],
            ]
        ));
    }
}
