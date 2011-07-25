<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_sanitize()
 *
 * @package phpMyAdmin-test
 */

/*
 * Include to test
 */
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/core.lib.php';

class PMA_sanitize_test extends PHPUnit_Framework_TestCase
{
    public function testXssInHref()
    {
        $this->assertEquals('[a@javascript:alert(\'XSS\');@target]link</a>',
            PMA_sanitize('[a@javascript:alert(\'XSS\');@target]link[/a]'));
    }

    public function testLink()
    {
        $this->assertEquals('<a href="./url.php?url=http%3A%2F%2Fwww.phpmyadmin.net%2F" target="target">link</a>',
            PMA_sanitize('[a@http://www.phpmyadmin.net/@target]link[/a]'));
    }

    public function testLinkDoc()
    {
        $this->assertEquals('<a href="./Documentation.html">doc</a>',
            PMA_sanitize('[a@./Documentation.html]doc[/a]'));
    }

    public function testLinkAndXssInHref()
    {
        $this->assertEquals('<a href="./Documentation.html">doc</a>[a@javascript:alert(\'XSS\');@target]link</a>',
            PMA_sanitize('[a@./Documentation.html]doc[/a][a@javascript:alert(\'XSS\');@target]link[/a]'));
    }

    public function testHtmlTags()
    {
        $this->assertEquals('&lt;div onclick=""&gt;',
            PMA_sanitize('<div onclick="">'));
    }

    public function testBbcoe()
    {
        $this->assertEquals('<strong>strong</strong>',
            PMA_sanitize('[b]strong[/b]'));
    }
}
?>
