<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Current;
use PhpMyAdmin\Sanitize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Sanitize::class)]
class SanitizeTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();
    }

    /**
     * Tests for proper escaping of XSS.
     */
    public function testXssInHref(): void
    {
        self::assertSame(
            '[a@javascript:alert(&#039;XSS&#039;);@target]link</a>',
            Sanitize::convertBBCode('[a@javascript:alert(\'XSS\');@target]link[/a]'),
        );
    }

    /**
     * Tests correct generating of link redirector.
     */
    public function testLink(): void
    {
        $lang = Current::$lang;

        Current::$lang = '';
        self::assertSame(
            '<a href="index.php?route=/url&url=https%3A%2F%2Fwww.phpmyadmin.net%2F" target="target">link</a>',
            Sanitize::convertBBCode('[a@https://www.phpmyadmin.net/@target]link[/a]'),
        );

        Current::$lang = $lang;
    }

    /**
     * Tests links to documentation.
     *
     * @param string $link     link
     * @param string $expected expected result
     */
    #[DataProvider('docLinks')]
    public function testDoc(string $link, string $expected): void
    {
        self::assertSame(
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
                . $expected . '" target="documentation">doclink</a>',
            Sanitize::convertBBCode('[doc@' . $link . ']doclink[/doc]'),
        );
    }

    /**
     * Data provider for sanitize [doc@foo] markup
     *
     * @return string[][]
     */
    public static function docLinks(): array
    {
        return [
            ['foo', 'setup.html%23foo'],
            ['cfg_TitleTable', 'config.html%23cfg_TitleTable'],
            ['faq3-11', 'faq.html%23faq3-11'],
            ['bookmarks@', 'bookmarks.html'],
        ];
    }

    /**
     * Tests link target validation.
     */
    public function testInvalidTarget(): void
    {
        self::assertSame(
            '[a@./Documentation.html@INVALID9]doc</a>',
            Sanitize::convertBBCode('[a@./Documentation.html@INVALID9]doc[/a]'),
        );
    }

    /**
     * Tests XSS escaping after valid link.
     */
    public function testLinkDocXss(): void
    {
        self::assertSame(
            '[a@./Documentation.html&quot; onmouseover=&quot;alert(foo)&quot;]doc</a>',
            Sanitize::convertBBCode('[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]'),
        );
    }

    /**
     * Tests proper handling of multi link code.
     */
    public function testLinkAndXssInHref(): void
    {
        self::assertSame(
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2F">doc</a>'
                . '[a@javascript:alert(&#039;XSS&#039;);@target]link</a>',
            Sanitize::convertBBCode(
                '[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]',
            ),
        );
    }

    /**
     * Test escaping of HTML tags
     */
    public function testHtmlTags(): void
    {
        self::assertSame(
            '&lt;div onclick=&quot;&quot;&gt;',
            Sanitize::convertBBCode('<div onclick="">'),
        );
    }

    /**
     * Tests basic BB code.
     */
    public function testBBCode(): void
    {
        self::assertSame(
            '<strong>strong</strong>',
            Sanitize::convertBBCode('[strong]strong[/strong]'),
        );
    }

    /**
     * Test for Sanitize::sanitizeFilename
     */
    public function testSanitizeFilename(): void
    {
        self::assertSame(
            'File_name_123',
            Sanitize::sanitizeFilename('File_name 123'),
        );
    }

    /**
     * Test for Sanitize::getJsValue
     *
     * @param string                   $key      Key
     * @param string|bool|int|string[] $value    Value
     * @param string                   $expected Expected output
     */
    #[DataProvider('variables')]
    public function testGetJsValue(string $key, string|bool|int|array $value, string $expected): void
    {
        self::assertSame($expected, Sanitize::getJsValue($key, $value));
    }

    /**
     * Provider for testFormat
     *
     * @return array<int, array{string, bool|int|string|string[], string}>
     */
    public static function variables(): array
    {
        return [
            ['foo', true, "foo = true;\n"],
            ['foo', false, "foo = false;\n"],
            ['foo', 100, "foo = 100;\n"],
            ['foo', 0, "foo = 0;\n"],
            ['foo', 'text', "foo = \"text\";\n"],
            ['foo', 'quote"', "foo = \"quote\\\"\";\n"],
            ['foo', 'apostroph\'', "foo = \"apostroph'\";\n"],
            ['foo', ['1', '2', '3'], "foo = [\"1\",\"2\",\"3\"];\n"],
            ['foo', 'bar"baz', "foo = \"bar\\\"baz\";\n"],
        ];
    }

    /**
     * Data provider for sanitize links
     *
     * @return array<int, array{bool, string, bool, bool}>
     */
    public static function dataProviderCheckLinks(): array
    {
        // Expected
        // The url
        // Allow http links
        // Allow other links
        return [
            [false, 'foo', false, false],
            [true, './docs/html/', false, false],
            [false, 'index.php', false, false],
            [false, './index.php', false, false],
            [true, './index.php?', false, false],
            [true, './index.php?route=/server/sql', false, false],
            [false, 'index.php?route=/server/sql', false, false],
            [false, 'ftp://ftp.example.com', false, false],
            [true, 'ftp://ftp.example.com', false, true],
            [false, 'mailto:admin@domain.tld', false, false],
            [true, 'mailto:admin@domain.tld', false, true],
            [false, 'index.php?route=/url&url=https://example.com', false, false],
            [true, 'index.php?route=/url&url=https%3a%2f%2fexample.com', false, false],
            [true, 'https://example.com', false, false],
            [false, 'http://example.com', false, false],
            [true, 'http://example.com', true, false],
        ];
    }

    /**
     * Tests link sanitize
     */
    #[DataProvider('dataProviderCheckLinks')]
    public function testCheckLink(bool $expected, string $url, bool $http, bool $other): void
    {
        self::assertSame(
            $expected,
            Sanitize::checkLink($url, $http, $other),
        );
    }
}
