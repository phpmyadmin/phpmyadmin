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
        self::assertSame(0, $this->font->getStringWidth('', 'arial', 10));

        // empty string
        self::assertSame(3, $this->font->getStringWidth(' ', 'arial', 10));

        // string "a"
        self::assertSame(6, $this->font->getStringWidth('a', 'arial', 10));

        // string "aa"
        self::assertSame(12, $this->font->getStringWidth('aa', 'arial', 10));

        // string "i"
        self::assertSame(3, $this->font->getStringWidth('i', 'arial', 10));

        // string "f"
        self::assertSame(3, $this->font->getStringWidth('f', 'arial', 10));

        // string "t"
        self::assertSame(3, $this->font->getStringWidth('t', 'arial', 10));

        // string "if"
        self::assertSame(5, $this->font->getStringWidth('if', 'arial', 10));

        // string "it"
        self::assertSame(6, $this->font->getStringWidth('it', 'arial', 10));

        // string "r"
        self::assertSame(4, $this->font->getStringWidth('r', 'arial', 10));

        // string "1"
        self::assertSame(5, $this->font->getStringWidth('1', 'arial', 10));

        // string "c"
        self::assertSame(5, $this->font->getStringWidth('c', 'arial', 10));

        // string "F"
        self::assertSame(7, $this->font->getStringWidth('F', 'arial', 10));

        // string "A"
        self::assertSame(7, $this->font->getStringWidth('A', 'arial', 10));

        // string "w"
        self::assertSame(8, $this->font->getStringWidth('w', 'arial', 10));

        // string "G"
        self::assertSame(8, $this->font->getStringWidth('G', 'arial', 10));

        // string "m"
        self::assertSame(9, $this->font->getStringWidth('m', 'arial', 10));

        // string "W"
        self::assertSame(10, $this->font->getStringWidth('W', 'arial', 10));

        // string "$"
        self::assertSame(3, $this->font->getStringWidth('$', 'arial', 10));
    }

    /**
     * Test getStringWidth with different fonts.
     */
    public function testGetStringWidthFont(): void
    {
        // string "phpMyAdmin", with Arial 10
        self::assertSame(59, $this->font->getStringWidth('phpMyAdmin', 'arial', 10));

        // string "phpMyAdmin", with No font
        self::assertSame(59, $this->font->getStringWidth('phpMyAdmin', '', 10));

        // string "phpMyAdmin", with Times 10
        self::assertSame(55, $this->font->getStringWidth('phpMyAdmin', 'times', 10));

        // string "phpMyAdmin", with Broadway 10
        self::assertSame(73, $this->font->getStringWidth('phpMyAdmin', 'broadway', 10));
    }

    /**
     * Test getStringWidth with different font sizes.
     */
    public function testGetStringWidthSize(): void
    {
        // string "phpMyAdmin", with font size 0
        self::assertSame(0, $this->font->getStringWidth('phpMyAdmin', 'arial', 0));

        // string "phpMyAdmin", with Arial 10
        self::assertSame(59, $this->font->getStringWidth('phpMyAdmin', 'arial', 10));

        // string "phpMyAdmin", with Arial 11
        self::assertSame(65, $this->font->getStringWidth('phpMyAdmin', 'arial', 11));

        // string "phpMyAdmin", with Arial 20
        self::assertSame(118, $this->font->getStringWidth('phpMyAdmin', 'arial', 20));
    }

    /**
     * Test getStringWidth with a custom charList.
     */
    public function testGetStringWidthCharLists(): void
    {
        // string "a", with invalid charlist (= array without proper structure)
        self::assertSame(6, $this->font->getStringWidth('a', 'arial', 10, ['list']));

        // string "a", with invalid charlist (= array without proper structure :
        // modifier is missing
        self::assertSame(6, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [['chars' => 'a']]
        ));

        // string "a", with invalid charlist (= array without proper structure :
        // chars is missing
        self::assertSame(6, $this->font->getStringWidth(
            'a',
            'arial',
            10,
            [['modifier' => 0.61]]
        ));

        // string "a", with invalid charlist (= array without proper structure :
        // chars is not an array
        self::assertSame(6, $this->font->getStringWidth(
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
        self::assertSame(7, $this->font->getStringWidth(
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
