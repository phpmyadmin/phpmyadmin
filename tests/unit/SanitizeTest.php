<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Sanitize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function implode;
use function range;
use function str_repeat;

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

    #[DataProvider('convertBBCodeProvider')]
    public function testConvertBbCode(string $expected, string $message): void
    {
        self::assertSame($expected, Sanitize::convertBBCode($message));
    }

    /** @return iterable<string, array{string, string}> */
    public static function convertBBCodeProvider(): iterable
    {
        yield '[em]' => ['<em>message</em>', '[em]message[/em]'];
        yield '[strong]' => ['<strong>message</strong>', '[strong]message[/strong]'];
        yield '[code]' => ['<code>message</code>', '[code]message[/code]'];
        yield '[kbd]' => ['<kbd>message</kbd>', '[kbd]message[/kbd]'];
        yield '[br]' => ['message<br>message', 'message[br]message'];
        yield '[sup]' => ['<sup>message</sup>', '[sup]message[/sup]'];
        yield '[dochelpicon]' => [
            'a<br>b<img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help">c',
            'a[br]b[dochelpicon]c',
        ];

        yield '[a] link and XSS in href' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2F">doc</a>'
                . '[a@javascript:alert(&#039;XSS&#039;);@target]link</a>',
            '[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]',
        ];

        yield '[a] XSS in href' => [
            '[a@javascript:alert(&#039;XSS&#039;);@target]link</a>',
            '[a@javascript:alert(\'XSS\');@target]link[/a]',
        ];

        yield '[a] XSS escaping after valid link' => [
            '[a@./Documentation.html&quot; onmouseover=&quot;alert(foo)&quot;]doc</a>',
            '[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]',
        ];

        yield '[a] target' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fwww.phpmyadmin.net%2F" target="target">link</a>',
            '[a@https://www.phpmyadmin.net/@target]link[/a]',
        ];

        yield '[a] invalid target' => [
            '[a@./Documentation.html@INVALID9]doc</a>',
            '[a@./Documentation.html@INVALID9]doc[/a]',
        ];

        yield '[doc] setup' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
            . 'setup.html%23foo" target="documentation">doclink</a>',
            '[doc@foo]doclink[/doc]',
        ];

        yield '[doc] config' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
            . 'config.html%23cfg_TitleTable" target="documentation">doclink</a>',
            '[doc@cfg_TitleTable]doclink[/doc]',
        ];

        yield '[doc] FAQ' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
            . 'faq.html%23faq3-11" target="documentation">doclink</a>',
            '[doc@faq3-11]doclink[/doc]',
        ];

        yield '[doc] link' => [
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
            . 'bookmarks.html" target="documentation">doclink</a>',
            '[doc@bookmarks@]doclink[/doc]',
        ];
    }

    public function testConvertBbCodeSafeParam(): void
    {
        self::assertSame(
            'a<em>&lt;div onclick=&quot;alert(&#039;XSS&#039;)&quot;&gt;b&lt;/div&gt;</em>c',
            Sanitize::convertBBCode('a[em]<div onclick="alert(\'XSS\')">b</div>[/em]c'),
        );
        self::assertSame(
            'a<em><div onclick="alert(\'XSS\')">b</div></em>c',
            Sanitize::convertBBCode('a[em]<div onclick="alert(\'XSS\')">b</div>[/em]c', true),
        );
    }

    #[DataProvider('providerTestSanitizeFileName')]
    public function testSanitizeFilename(string $expected, string $input, bool $replaceDot): void
    {
        self::assertSame($expected, Sanitize::sanitizeFilename($input, $replaceDot));
    }

    /** @return list<array{string,string,bool}> */
    public static function providerTestSanitizeFileName(): array
    {
        return [
            ['Hello123', 'Hello123', false],
            ['宮保雞丁', '宮保雞丁', false],
            ['Україна', 'Україна', false],
            ['-_-', '-.-', true],
            ['-.-', '-.-', false],
            ['___', '"\'"', false],
            ['_test_', '<test>', false],
            ['Hello__World_', "Hello\r\nWorld!", false],
            ['_', "\u{fffd}", false],
            ['_', '🚀', false],
            [str_repeat('_', 32), implode('', range("\0", "\x1f")), false],
        ];
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
