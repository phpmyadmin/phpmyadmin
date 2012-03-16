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

class PMA_sanitize_test extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $_SESSION[' PMA_token '] = 'token';
    }

    /**
     * Tests for proper escaping of XSS.
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
     */
    public function testLink()
    {
        unset($GLOBALS['server']);
        unset($GLOBALS['lang']);
        unset($GLOBALS['collation_connection']);
        $this->assertEquals(
            '<a href="./url.php?url=http%3A%2F%2Fwww.phpmyadmin.net%2F&amp;token=token" target="target">link</a>',
            PMA_sanitize('[a@http://www.phpmyadmin.net/@target]link[/a]')
        );
    }

    /**
     * Tests links to documentation.
     */
    public function testLinkDoc()
    {
        $this->assertEquals(
            '<a href="./Documentation.html">doc</a>',
            PMA_sanitize('[a@./Documentation.html]doc[/a]')
        );
    }

    /**
     * Tests link target validation.
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
     */
    public function testLinkAndXssInHref()
    {
        $this->assertEquals(
            '<a href="./Documentation.html">doc</a>[a@javascript:alert(\'XSS\');@target]link</a>',
            PMA_sanitize('[a@./Documentation.html]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]')
        );
    }

    /**
     * Test escaping of HTML tags
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
     */
    public function testBBCode()
    {
        $this->assertEquals(
            '<strong>strong</strong>',
            PMA_sanitize('[b]strong[/b]')
        );
    }

    /**
     * Tests output escaping.
     */
    public function testEscape()
    {
        $this->assertEquals(
            '&lt;strong&gt;strong&lt;/strong&gt;',
            PMA_sanitize('[strong]strong[/strong]', true)
        );
    }
}
?>
