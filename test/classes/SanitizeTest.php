<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in Sanitize class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Sanitize;
use PHPUnit\Framework\TestCase;

/**
 * Tests for methods in Sanitize class
 *
 * @package PhpMyAdmin-test
 */
class SanitizeTest extends TestCase
{
    /**
     * Tests for proper escaping of XSS.
     *
     * @return void
     */
    public function testXssInHref()
    {
        $this->assertEquals(
            '[a@javascript:alert(\'XSS\');@target]link</a>',
            Sanitize::sanitizeMessage('[a@javascript:alert(\'XSS\');@target]link[/a]')
        );
    }

    /**
     * Tests correct generating of link redirector.
     *
     * @return void
     */
    public function testLink()
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
     * @return void
     *
     * @dataProvider docLinks
     */
    public function testDoc($link, $expected): void
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F' . $expected . '" target="documentation">doclink</a>',
            Sanitize::sanitizeMessage('[doc@' . $link . ']doclink[/doc]')
        );
    }

    /**
     * Data provider for sanitize [doc@foo] markup
     *
     * @return array
     */
    public function docLinks()
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
     *
     * @return void
     */
    public function testInvalidTarget()
    {
        $this->assertEquals(
            '[a@./Documentation.html@INVALID9]doc</a>',
            Sanitize::sanitizeMessage('[a@./Documentation.html@INVALID9]doc[/a]')
        );
    }

    /**
     * Tests XSS escaping after valid link.
     *
     * @return void
     */
    public function testLinkDocXss()
    {
        $this->assertEquals(
            '[a@./Documentation.html" onmouseover="alert(foo)"]doc</a>',
            Sanitize::sanitizeMessage('[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]')
        );
    }

    /**
     * Tests proper handling of multi link code.
     *
     * @return void
     */
    public function testLinkAndXssInHref()
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2F">doc</a>[a@javascript:alert(\'XSS\');@target]link</a>',
            Sanitize::sanitizeMessage('[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]')
        );
    }

    /**
     * Test escaping of HTML tags
     *
     * @return void
     */
    public function testHtmlTags()
    {
        $this->assertEquals(
            '&lt;div onclick=""&gt;',
            Sanitize::sanitizeMessage('<div onclick="">')
        );
    }

    /**
     * Tests basic BB code.
     *
     * @return void
     */
    public function testBBCode()
    {
        $this->assertEquals(
            '<strong>strong</strong>',
            Sanitize::sanitizeMessage('[strong]strong[/strong]')
        );
    }

    /**
     * Tests output escaping.
     *
     * @return void
     */
    public function testEscape()
    {
        $this->assertEquals(
            '&lt;strong&gt;strong&lt;/strong&gt;',
            Sanitize::sanitizeMessage('[strong]strong[/strong]', true)
        );
    }

    /**
     * Test for Sanitize::sanitizeFilename
     *
     * @return void
     */
    public function testSanitizeFilename()
    {
        $this->assertEquals(
            'File_name_123',
            Sanitize::sanitizeFilename('File_name 123')
        );
    }

    /**
     * Test for Sanitize::getJsValue
     *
     * @param string $key      Key
     * @param string $value    Value
     * @param string $expected Expected output
     *
     * @dataProvider variables
     *
     * @return void
     */
    public function testGetJsValue($key, $value, $expected): void
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
     *
     * @return void
     */
    public function testJsFormat()
    {
        $this->assertEquals("`foo`", Sanitize::jsFormat('foo'));
    }

    /**
     * Provider for testFormat
     *
     * @return array
     */
    public function variables()
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
     * @return void
     *
     * @dataProvider escapeDataProvider
     */
    public function testEscapeJsString($target, $source): void
    {
        $this->assertEquals($target, Sanitize::escapeJsString($source));
    }

    /**
     * Data provider for testEscape
     *
     * @return array data for testEscape test case
     */
    public function escapeDataProvider()
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
     *
     * @return void
     */
    public function testRemoveRequestVars()
    {
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
}
