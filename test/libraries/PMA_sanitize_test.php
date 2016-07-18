<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_sanitize()
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';

/**
 * tests for PMA_sanitize()
 *
 * @package PhpMyAdmin-test
 */
class PMA_Sanitize_Test extends PHPUnit_Framework_TestCase
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
            PMA_sanitize('[a@javascript:alert(\'XSS\');@target]link[/a]')
        );
    }

    /**
     * Tests correct generating of link redirector.
     *
     * @return void
     */
    public function testLink()
    {
        unset($GLOBALS['server']);
        unset($GLOBALS['lang']);
        unset($GLOBALS['collation_connection']);
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fwww.phpmyadmin.net%2F" target="target">link</a>',
            PMA_sanitize('[a@https://www.phpmyadmin.net/@target]link[/a]')
        );
    }

    /**
     * Tests links to documentation.
     *
     * @return void
     */
    public function testDoc()
    {
        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fsetup.html%23foo" target="documentation">doclink</a>',
            PMA_sanitize('[doc@foo]doclink[/doc]')
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
            PMA_sanitize('[a@./Documentation.html@INVALID9]doc[/a]')
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
            PMA_sanitize('[a@./Documentation.html" onmouseover="alert(foo)"]doc[/a]')
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
            PMA_sanitize('[a@https://docs.phpmyadmin.net/]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]')
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
            PMA_sanitize('<div onclick="">')
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
            PMA_sanitize('[strong]strong[/strong]')
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
            PMA_sanitize('[strong]strong[/strong]', true)
        );
    }

    /**
     * Test for PMA_sanitizeFilename
     *
     * @return void
     */
    public function testSanitizeFilename()
    {
        $this->assertEquals(
            'File_name_123',
            PMA_sanitizeFilename('File_name 123')
        );
    }
}
?>
