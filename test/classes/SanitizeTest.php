<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Sanitize;

class SanitizeTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
    }

    /**
     * Tests for proper escaping of XSS.
     */
    public function testXssInHref(): void
    {
        $this->assertEquals(
            '[a@javascript:alert(\'XSS\');@target]link</a>',
            Sanitize::sanitizeMessage('[a@javascript:alert(\'XSS\');@target]link[/a]')
        );
    }

    /**
     * Tests correct generating of link redirector.
     */
    public function testLink(): void
    {
        $lang = $GLOBALS['lang'];

        unset($GLOBALS['server']);
        unset($GLOBALS['lang']);
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fwww.phpmyadmin.net%2F" target="target">link</a>',
            Sanitize::sanitizeMessage('[a@https://www.phpmyadmin.net/@target]link[/a]')
        );

        $GLOBALS['lang'] = $lang;
    }

    /**
     * Tests links to documentation.
     *
     * @param string $link     link
     * @param string $expected expected result
     *
     * @dataProvider docLinks
     */
    public function testDoc(string $link, string $expected): void
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F'
                . $expected . '" target="documentation">doclink</a>',
            Sanitize::sanitizeMessage('[doc@' . $link . ']doclink[/doc]')
        );
    }

    /**
     * Data provider for sanitize [doc@foo] markup
     *
     * @return array
     */
    public function docLinks(): array
    {
        return [
            [
                'foo',
                'setup.html%23foo',
            ],
            [
                'cfg_TitleTable',
                'config.html%23cfg_TitleTable',
            ],
            [
                'faq3-11',
                'faq.html%23faq3-11',
            ],
            [
                'bookmarks@',
                'bookmarks.html',
            ],
        ];
    }

    /**
     * Tests link target validation.
     */
    public function testInvalidTarget(): void
    {
        $this->assertEquals(
            '[a@./Documentation.html@INVALID9]doc</a>',
            Sanitize::sanitizeMessage('[a@./Documentation.html@INVALID9]doc[/a]')
        );
    }

    /**
     * Tests XSS escaping after valid link.
     */
    public function testLinkDocXss(): void
    {
        $this->assertEquals(
            '[a@./Documentation.html" onmouseover="alert(foo)"]doc</a>',
            Sanitize::sanitizeMessage('[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]')
        );
    }

    /**
     * Tests proper handling of multi link code.
     */
    public function testLinkAndXssInHref(): void
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2F">doc</a>'
                . '[a@javascript:alert(\'XSS\');@target]link</a>',
            Sanitize::sanitizeMessage(
                '[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]'
            )
        );
    }

    /**
     * Test escaping of HTML tags
     */
    public function testHtmlTags(): void
    {
        $this->assertEquals(
            '&lt;div onclick=""&gt;',
            Sanitize::sanitizeMessage('<div onclick="">')
        );
    }

    /**
     * Tests basic BB code.
     */
    public function testBBCode(): void
    {
        $this->assertEquals(
            '<strong>strong</strong>',
            Sanitize::sanitizeMessage('[strong]strong[/strong]')
        );
    }

    /**
     * Tests output escaping.
     */
    public function testEscape(): void
    {
        $this->assertEquals(
            '&lt;strong&gt;strong&lt;/strong&gt;',
            Sanitize::sanitizeMessage('[strong]strong[/strong]', true)
        );
    }

    /**
     * Test for Sanitize::sanitizeFilename
     */
    public function testSanitizeFilename(): void
    {
        $this->assertEquals(
            'File_name_123',
            Sanitize::sanitizeFilename('File_name 123')
        );
    }

    /**
     * Test for Sanitize::getJsValue
     *
     * @param string          $key      Key
     * @param string|bool|int $value    Value
     * @param string          $expected Expected output
     *
     * @dataProvider variables
     */
    public function testGetJsValue(string $key, $value, string $expected): void
    {
        $this->assertEquals($expected, Sanitize::getJsValue($key, $value));
        $this->assertEquals('foo = 100', Sanitize::getJsValue('foo', '100', false));
        $array = [
            '1',
            '2',
            '3',
        ];
        $this->assertEquals(
            "foo = [\"1\",\"2\",\"3\",];\n",
            Sanitize::getJsValue('foo', $array)
        );
        $this->assertEquals(
            "foo = \"bar\\\"baz\";\n",
            Sanitize::getJsValue('foo', 'bar"baz')
        );
    }

    /**
     * Test for Sanitize::jsFormat
     */
    public function testJsFormat(): void
    {
        $this->assertEquals('`foo`', Sanitize::jsFormat('foo'));
    }

    /**
     * Provider for testFormat
     *
     * @return array
     */
    public function variables(): array
    {
        return [
            [
                'foo',
                true,
                "foo = true;\n",
            ],
            [
                'foo',
                false,
                "foo = false;\n",
            ],
            [
                'foo',
                100,
                "foo = 100;\n",
            ],
            [
                'foo',
                0,
                "foo = 0;\n",
            ],
            [
                'foo',
                'text',
                "foo = \"text\";\n",
            ],
            [
                'foo',
                'quote"',
                "foo = \"quote\\\"\";\n",
            ],
            [
                'foo',
                'apostroph\'',
                "foo = \"apostroph\\'\";\n",
            ],
        ];
    }

    /**
     * Sanitize::escapeJsString tests
     *
     * @param string $target expected output
     * @param string $source string to be escaped
     *
     * @dataProvider escapeDataProvider
     */
    public function testEscapeJsString(string $target, string $source): void
    {
        $this->assertEquals($target, Sanitize::escapeJsString($source));
    }

    /**
     * Data provider for testEscape
     *
     * @return array data for testEscape test case
     */
    public function escapeDataProvider(): array
    {
        return [
            [
                '\\\';',
                '\';',
            ],
            [
                '\r\n\\\'<scrIpt></\' + \'script>',
                "\r\n'<scrIpt></sCRIPT>",
            ],
            [
                '\\\';[XSS]',
                '\';[XSS]',
            ],
            [
                '</\' + \'script></head><body>[HTML]',
                '</SCRIPT></head><body>[HTML]',
            ],
            [
                '\"\\\'\\\\\\\'\"',
                '"\'\\\'"',
            ],
            [
                "\\\\\'\'\'\'\'\'\'\'\'\'\'\'\\\\",
                "\\''''''''''''\\",
            ],
        ];
    }

    /**
     * Test for removeRequestVars
     */
    public function testRemoveRequestVars(): void
    {
        $GLOBALS['_POST'] = [];
        $_REQUEST['foo'] = 'bar';
        $_REQUEST['allow'] = 'all';
        $_REQUEST['second'] = 1;
        $allow_list = [
            'allow',
            'second',
        ];
        Sanitize::removeRequestVars($allow_list);
        $this->assertArrayNotHasKey('foo', $_REQUEST);
        $this->assertArrayNotHasKey('second', $_REQUEST);
        $this->assertArrayHasKey('allow', $_REQUEST);
    }

    /**
     * Data provider for sanitize links
     *
     * @return array
     */
    public function dataProviderCheckLinks(): array
    {
        // Expected
        // The url
        // Allow http links
        // Allow other links
        return [
            [
                false,
                'foo',
                false,
                false,
            ],
            [
                true,
                './doc/html/',
                false,
                false,
            ],
            [
                false,
                'index.php',
                false,
                false,
            ],
            [
                false,
                './index.php',
                false,
                false,
            ],
            [
                true,
                './index.php?',
                false,
                false,
            ],
            [
                true,
                './index.php?route=/server/sql',
                false,
                false,
            ],
            [
                false,
                'index.php?route=/server/sql',
                false,
                false,
            ],
            [
                false,
                'ftp://ftp.example.com',
                false,
                false,
            ],
            [
                true,
                'ftp://ftp.example.com',
                false,
                true,
            ],
            [
                false,
                'mailto:admin@domain.tld',
                false,
                false,
            ],
            [
                true,
                'mailto:admin@domain.tld',
                false,
                true,
            ],
            [
                false,
                './url.php?url=https://example.com',
                false,
                false,
            ],
            [
                true,
                './url.php?url=https%3a%2f%2fexample.com',
                false,
                false,
            ],
            [
                true,
                'https://example.com',
                false,
                false,
            ],
            [
                false,
                'http://example.com',
                false,
                false,
            ],
            [
                true,
                'http://example.com',
                true,
                false,
            ],
        ];
    }

    /**
     * Tests link sanitize
     *
     * @dataProvider dataProviderCheckLinks
     */
    public function testCheckLink(bool $expected, string $url, bool $http, bool $other): void
    {
        $this->assertSame(
            $expected,
            Sanitize::checkLink($url, $http, $other)
        );
    }
}
