<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in Sanitize class
 *
 * @package PhpMyAdmin-test
 */
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
     * Setup various pre conditions
     *
     * @return void
     */
    function setUp()
    {
    }

    /**
     * Tests for proper escaping of XSS.
     *
     * @return void
     */
    public function testXssInHref()
    {
        $this->assertEquals(
            '[a@javascript:alert(\'XSS\');@target]link</a>',
            Sanitize::sanitize('[a@javascript:alert(\'XSS\');@target]link[/a]')
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
            Sanitize::sanitize('[a@https://www.phpmyadmin.net/@target]link[/a]')
        );

        $GLOBALS['lang'] = $lang;
    }

    /**
     * Tests links to documentation.
     *
     * @return void
     *
     * @dataProvider docLinks
     */
    public function testDoc($link, $expected)
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2F' . $expected . '" target="documentation">doclink</a>',
            Sanitize::sanitize('[doc@' . $link . ']doclink[/doc]')
        );
    }

    /**
     * Data provider for sanitize [doc@foo] markup
     *
     * @return array
     */
    public function docLinks()
    {
        return array(
            array('foo', 'setup.html%23foo'),
            array('cfg_TitleTable', 'config.html%23cfg_TitleTable'),
            array('faq3-11', 'faq.html%23faq3-11'),
            array('bookmarks@', 'bookmarks.html'),
        );
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
            Sanitize::sanitize('[a@./Documentation.html@INVALID9]doc[/a]')
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
            Sanitize::sanitize('[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]')
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
            Sanitize::sanitize('[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]')
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
            Sanitize::sanitize('<div onclick="">')
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
            Sanitize::sanitize('[strong]strong[/strong]')
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
            Sanitize::sanitize('[strong]strong[/strong]', true)
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
    public function testGetJsValue($key, $value, $expected)
    {
        $this->assertEquals($expected, Sanitize::getJsValue($key, $value));
        $this->assertEquals('foo = 100', Sanitize::getJsValue('foo', '100', false));
        $array = array('1','2','3');
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
        return array(
            array('foo', true, "foo = true;\n"),
            array('foo', false, "foo = false;\n"),
            array('foo', 100, "foo = 100;\n"),
            array('foo', 0, "foo = 0;\n"),
            array('foo', 'text', "foo = \"text\";\n"),
            array('foo', 'quote"', "foo = \"quote\\\"\";\n"),
            array('foo', 'apostroph\'', "foo = \"apostroph\\'\";\n"),
        );
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
    public function testEscapeJsString($target, $source)
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
        return array(
            array('\\\';', '\';'),
            array('\r\n\\\'<scrIpt></\' + \'script>', "\r\n'<scrIpt></sCRIPT>"),
            array('\\\';[XSS]', '\';[XSS]'),
            array(
                    '</\' + \'script></head><body>[HTML]',
                    '</SCRIPT></head><body>[HTML]'
            ),
            array('\"\\\'\\\\\\\'\"', '"\'\\\'"'),
            array("\\\\\'\'\'\'\'\'\'\'\'\'\'\'\\\\", "\\''''''''''''\\")
        );
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
        $allow_list = array('allow', 'second');
        Sanitize::removeRequestVars($allow_list);
        $this->assertArrayNotHasKey('foo', $_REQUEST);
        $this->assertArrayNotHasKey('second', $_REQUEST);
        $this->assertArrayHasKey('allow', $_REQUEST);
    }

}
